<?php
namespace WMS\Library\BusinessRulesEngine\Loader;

use WMS\Library\BusinessRulesEngine\RuleCollection;
use WMS\Library\BusinessRulesEngine\Rule;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Config\Util\XmlUtils;

/**
 * XmlFileLoader loads XML rules files.
 *
 * @author Andrew Moore <me@andrewmoore.ca>
 */
class XmlFileLoader extends FileLoader
{
    const NAMESPACE_URI = 'http://wolfmicrosystems.com/schema/business-rules-engine';
    const SCHEME_PATH = '/schema/business-rules-engine/business-rules-engine-1.0.xsd';

    /**
     * Loads an XML file.
     *
     * @param string      $file An XML file path
     * @param string|null $type The resource type
     *
     * @return RuleCollection A RuleCollection instance
     *
     * @throws \InvalidArgumentException When the file cannot be loaded or when the XML cannot be
     *                                   parsed because it does not validate against the scheme.
     *
     * @api
     */
    public function load($file, $type = null)
    {
        $path = $this->locator->locate($file);

        $xml = $this->loadFile($path);

        $collection = new RuleCollection();
        $collection->addResource(new FileResource($path));

        // process rules and imports
        foreach ($xml->documentElement->childNodes as $node) {
            if (!$node instanceof \DOMElement) {
                continue;
            }

            $this->parseNode($collection, $node, $path, $file);
        }

        return $collection;
    }

    /**
     * Parses a node from a loaded XML file.
     *
     * @param RuleCollection $collection Collection to associate with the node
     * @param \DOMElement    $node       Element to parse
     * @param string         $path       Full path of the XML file being processed
     * @param string         $file       Loaded file name
     *
     * @throws \InvalidArgumentException When the XML is invalid
     */
    protected function parseNode(RuleCollection $collection, \DOMElement $node, $path, $file)
    {
        if (self::NAMESPACE_URI !== $node->namespaceURI) {
            return;
        }

        switch ($node->localName) {
            case 'rule':
                $this->parseRule($collection, $node, $path);
                break;
            case 'import':
                $this->parseImport($collection, $node, $path, $file);
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Unknown tag "%s" used in file "%s". Expected "rule" or "import".', $node->localName, $path));
        }
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && 'xml' === pathinfo($resource, PATHINFO_EXTENSION) && (!$type || 'xml' === $type);
    }

    /**
     * Parses a rule and adds it to the RuleCollection.
     *
     * @param RuleCollection $collection RuleCollection instance
     * @param \DOMElement    $node       Element to parse that represents a Rule
     * @param string         $path       Full path of the XML file being processed
     *
     * @throws \InvalidArgumentException When the XML is invalid
     */
    protected function parseRule(RuleCollection $collection, \DOMElement $node, $path)
    {
        if ('' === ($id = $node->getAttribute('id'))) {
            throw new \InvalidArgumentException(sprintf('The <rule> element in file "%s" must have an "id" attribute.', $path));
        }

        if ('' === ($expression = $node->getAttribute('expression'))) {
            throw new \InvalidArgumentException(sprintf('The <rule> element in file "%s" must have an "expression" attribute.', $path));
        }

        $rule = new Rule($expression);

        list($tags) = $this->parseConfigs($node, $path);

        foreach ($tags as $tagName => $tagAttrs) {
            $rule->addTag($tagName, $tagAttrs);
        }

        $collection->add($id, $rule);
    }

    /**
     * Parses an import and adds the rules in the resource to the RuleCollection.
     *
     * @param RuleCollection $collection RuleCollection instance
     * @param \DOMElement    $node       Element to parse that represents a Rule
     * @param string         $path       Full path of the XML file being processed
     * @param string         $file       Loaded file name
     *
     * @throws \InvalidArgumentException When the XML is invalid
     */
    protected function parseImport(RuleCollection $collection, \DOMElement $node, $path, $file)
    {
        if ('' === $resource = $node->getAttribute('resource')) {
            throw new \InvalidArgumentException(sprintf('The <import> element in file "%s" must have a "resource" attribute.', $path));
        }

        $type = $node->getAttribute('type');
        list($tags) = $this->parseConfigs($node, $path);

        $this->setCurrentDir(dirname($path));

        $subCollection = $this->import($resource, ('' !== $type ? $type : null), false, $file);

        /* @var $subCollection RuleCollection */
        foreach ($tags as $tagName => $tagAttrs) {
            $subCollection->addTag($tagName, $tagAttrs);
        }

        $collection->addCollection($subCollection);
    }

    /**
     * Loads an XML file.
     *
     * @param string $file An XML file path
     *
     * @return \DOMDocument
     *
     * @throws \InvalidArgumentException When loading of XML file fails because of syntax errors
     *                                   or when the XML structure is not as expected by the scheme -
     *                                   see validate()
     */
    protected function loadFile($file)
    {
        return XmlUtils::loadFile($file, __DIR__ . static::SCHEME_PATH);
    }

    /**
     * Parses the config elements (default, requirement, option).
     *
     * @param \DOMElement $node Element to parse that contains the configs
     * @param string      $path Full path of the XML file being processed
     *
     * @return array An array with the defaults as first item, requirements as second and options as third.
     *
     * @throws \InvalidArgumentException When the XML is invalid
     */
    private function parseConfigs(\DOMElement $node, $path)
    {
        return array($this->parseTags($node, $path));
    }

    private function parseTags(\DOMElement $node, $path)
    {
        /** @var \DOMElement[] $tagNodes */
        $tagNodes = $node->getElementsByTagNameNS(self::NAMESPACE_URI, 'tag');

        $tags = array();

        foreach ($tagNodes as $tagNode) {
            $parameters = array();
            foreach ($tagNode->attributes as $name => $attr) {
                /** @var \DOMAttr $attr */
                if ('name' === $name) {
                    continue;
                }

                $value = $attr->value;

                if (false !== strpos($name, '-') && false === strpos($name, '_') && !array_key_exists($normalizedName = str_replace('-', '_', $name), $parameters)) {
                    $parameters[$normalizedName] = XmlUtils::phpize($value);
                }
                // keep not normalized key for BC too
                $parameters[$name] = XmlUtils::phpize($value);
            }

            $tagName = (string)$tagNode->getAttribute('name');

            $tags[$tagName] = $parameters;
        }

        return $tags;
    }
}
