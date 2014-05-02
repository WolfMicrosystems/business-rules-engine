<?php
namespace WMS\Library\BusinessRulesEngine\Loader;

use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Yaml\Parser as YamlParser;
use WMS\Library\BusinessRulesEngine\Rule;
use WMS\Library\BusinessRulesEngine\RuleCollection;

class YamlFileLoader extends FileLoader
{
    private static $availableKeys = array(
        'resource',
        'type',
        'expression',
        'tags'
    );
    private $yamlParser;

    /**
     * Loads a resource.
     *
     * @param mixed  $file The resource
     * @param string $type The resource type
     *
     * @throws \InvalidArgumentException
     * @return \WMS\Library\BusinessRulesEngine\RuleCollection
     */
    public function load($file, $type = null)
    {
        $path = $this->locator->locate($file);

        if (!stream_is_local($path)) {
            throw new \InvalidArgumentException(sprintf('This is not a local file "%s".', $path));
        }

        if (!file_exists($path)) {
            throw new \InvalidArgumentException(sprintf('File "%s" not found.', $path));
        }

        if (null === $this->yamlParser) {
            $this->yamlParser = new YamlParser();
        }

        $config = $this->yamlParser->parse(file_get_contents($path));

        $collection = new RuleCollection();
        $collection->addResource(new FileResource($path));

        // empty file
        if (null === $config) {
            return $collection;
        }

        // not an array
        if (!is_array($config)) {
            throw new \InvalidArgumentException(sprintf('The file "%s" must contain a YAML array.', $path));
        }

        foreach ($config as $name => $subConfig) {
            $this->validate($subConfig, $name, $path);

            if (isset($subConfig['resource'])) {
                $this->parseImport($collection, $subConfig, $path, $file);
            } else {
                $this->parseRule($collection, $name, $subConfig, $path);
            }
        }

        return $collection;
    }

    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed  $resource A resource
     * @param string $type     The resource type
     *
     * @return Boolean true if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && 'yml' === pathinfo($resource, PATHINFO_EXTENSION) && (!$type || 'yaml' === $type);
    }

    /**
     * Parses a rule and adds it to the RuleCollection.
     *
     * @param RuleCollection $collection A RouteCollection instance
     * @param string         $name       Rule name
     * @param array          $config     Rule definition
     * @param string         $path       Full path of the YAML file being processed
     */
    protected function parseRule(RuleCollection $collection, $name, array $config, $path)
    {
        $expression = isset($config['expression']) ? $config['expression'] : null;
        $tags = isset($config['tags']) ? $this->parseTags($config['tags']) : array();

        $rule = new Rule($expression);

        foreach ($tags as $tagName => $tagAttr) {
            $rule->addTag($tagName, $tagAttr);
        }

        $collection->add($name, $rule);
    }

    /**
     * Parses an import and adds the rules in the resource to the RuleCollection.
     *
     * @param RuleCollection $collection A RuleCollection instance
     * @param array          $config     Rule definition
     * @param string         $path       Full path of the YAML file being processed
     * @param string         $file       Loaded file name
     */
    protected function parseImport(RuleCollection $collection, array $config, $path, $file)
    {
        $type = isset($config['type']) ? $config['type'] : null;
        $tags = isset($config['tags']) ? $this->parseTags($config['tags']) : array();
        $this->setCurrentDir(dirname($path));

        $subCollection = $this->import($config['resource'], $type, false, $file);
        /* @var $subCollection RuleCollection */

        foreach ($tags as $tagName => $tagAttr) {
            $subCollection->addTag($tagName, $tagAttr);
        }

        $collection->addCollection($subCollection);
    }

    protected function parseTags($tags)
    {
        $properTags = array();

        foreach ($tags as $tag) {
            $name = $tag['name'];
            unset($tag['name']);

            $properTags[$name] = $tag ? : array();
        }

        return $properTags;
    }

    /**
     * Validates the rule configuration.
     *
     * @param array  $config A resource config
     * @param string $name   The config key
     * @param string $path   The loaded file path
     *
     * @throws \InvalidArgumentException If one of the provided config keys is not supported,
     *                                   something is missing or the combination is nonsense
     */
    protected function validate($config, $name, $path)
    {
        if (!is_array($config)) {
            throw new \InvalidArgumentException(sprintf('The definition of "%s" in "%s" must be a YAML array.', $name, $path));
        }
        if ($extraKeys = array_diff(array_keys($config), self::$availableKeys)) {
            throw new \InvalidArgumentException(sprintf(
                'The routing file "%s" contains unsupported keys for "%s": "%s". Expected one of: "%s".',
                $path,
                $name,
                implode('", "', $extraKeys),
                implode('", "', self::$availableKeys)
            ));
        }
        if (isset($config['resource']) && isset($config['expression'])) {
            throw new \InvalidArgumentException(sprintf(
                'The business rule file "%s" must not specify both the "resource" key and the "expression" key for "%s". Choose between an import and a rule definition.',
                $path,
                $name
            ));
        }
        if (!isset($config['resource']) && isset($config['type'])) {
            throw new \InvalidArgumentException(sprintf(
                'The "type" key for the rule definition "%s" in "%s" is unsupported. It is only available for imports in combination with the "resource" key.',
                $name,
                $path
            ));
        }
        if (!isset($config['resource']) && !isset($config['expression'])) {
            throw new \InvalidArgumentException(sprintf(
                'You must define an "expression" for the rule "%s" in file "%s".',
                $name,
                $path
            ));
        }
        if (isset($config['tags']) && !is_array($config['tags'])) {
            throw new \InvalidArgumentException(sprintf(
                'The "tags" key for the rule definition "%s" in "%s" contains unsupported data. Each tag defined must be an array with at least the "name" element set to a string.',
                $name,
                $path
            ));
        } elseif (isset($config['tags'])) {
            foreach ($config['tags'] as $tag) {
                if (!isset($tag['name'])) {
                    throw new \InvalidArgumentException(sprintf(
                        'The "tags" key for the rule definition "%s" in "%s" contains unsupported data. Each tag defined must be an array with at least the "name" element set to a string.',
                        $name,
                        $path
                    ));
                }
            }
        }
    }
}