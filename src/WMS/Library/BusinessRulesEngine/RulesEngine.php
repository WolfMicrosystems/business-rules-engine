<?php
namespace WMS\Library\BusinessRulesEngine;

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\ExpressionLanguage\ParserCache\ArrayParserCache;
use Symfony\Component\ExpressionLanguage\ParserCache\ParserCacheInterface;
use WMS\Library\BusinessRulesEngine\Dumper\RuleCollectionDumperInterface;
use WMS\Library\BusinessRulesEngine\ExpressionLanguage\ExtensibleExpressionLanguage;
use WMS\Library\BusinessRulesEngine\ExpressionLanguage\Extension\ExtensionInterface;

class RulesEngine
{
    protected $loader;
    protected $resource;
    protected $expressionLanguage;
    protected $rules;

    /**
     * @var array
     */
    protected $options = array();

    public function __construct(LoaderInterface $loader, $resource, ParserCacheInterface $expressionCache = null, array $options = array())
    {
        $this->setOptions($options);

        $this->loader = $loader;
        $this->resource = $resource;
        $this->expressionLanguage = new ExtensibleExpressionLanguage($expressionCache ? : new ArrayParserCache());
    }

    /**
     * Sets options.
     *
     * Available options:
     *
     *   * cache_dir:     The cache directory (or null to disable caching)
     *   * debug:         Whether to enable debugging or not (false by default)
     *   * resource_type: Type hint for the main resource (optional)
     *
     * @param array $options An array of options
     *
     * @throws \InvalidArgumentException When unsupported option is provided
     */
    public function setOptions(array $options)
    {
        $this->options = array(
            'cache_dir'                    => null,
            'debug'                        => false,
            'rule_collection_dumper_class' => 'WMS\\Library\\BusinessRulesEngine\\Dumper\\PhpRuleCollectionDumper',
            'rule_collection_cache_class'  => 'ProjectRuleCollection',
            'resource_type'                => null,
        );

        // check option names and live merge, if errors are encountered Exception will be thrown
        $invalid = array();
        foreach ($options as $key => $value) {
            if (array_key_exists($key, $this->options)) {
                $this->options[$key] = $value;
            } else {
                $invalid[] = $key;
            }
        }

        if ($invalid) {
            throw new \InvalidArgumentException(sprintf('The Rule Engine does not support the following options: "%s".', implode('", "', $invalid)));
        }
    }

    /**
     * Sets an option.
     *
     * @param string $key   The key
     * @param mixed  $value The value
     *
     * @throws \InvalidArgumentException
     */
    public function setOption($key, $value)
    {
        if (!array_key_exists($key, $this->options)) {
            throw new \InvalidArgumentException(sprintf('The Rule Engine does not support the "%s" option.', $key));
        }

        $this->options[$key] = $value;
    }


    /**
     * Gets an option value.
     *
     * @param string $key The key
     *
     * @return mixed The value
     *
     * @throws \InvalidArgumentException
     */
    public function getOption($key)
    {
        if (!array_key_exists($key, $this->options)) {
            throw new \InvalidArgumentException(sprintf('The Rule Engine does not support the "%s" option.', $key));
        }

        return $this->options[$key];
    }

    /**
     * @return RuleCollection
     */
    public function getRuleCollection()
    {
        if (null !== $this->rules) {
            return $this->rules;
        }

        if (null === $this->options['cache_dir'] || null === $this->options['rule_collection_cache_class']) {
            return $this->rules = $this->loader->load($this->resource, $this->options['resource_type']);
        }

        $class = $this->options['rule_collection_cache_class'];
        $cache = new ConfigCache($this->options['cache_dir'] . '/' . $class . '.php', $this->options['debug']);
        if (!$cache->isFresh()) {
            /** @var RuleCollection $rules */
            $rules = $this->loader->load($this->resource, $this->options['resource_type']);

            $dumper = $this->getRuleCollectionDumperInstance($rules);

            $options = array(
                'class'      => $class,
                'base_class' => 'WMS\\Library\\BusinessRulesEngine\\RuleCollection',
            );

            $cache->write($dumper->dump($options), $rules->getResources());
        }

        require_once $cache;

        return $this->rules = new $class();
    }

    /**
     * @param RuleCollection $rules
     * @return RuleCollectionDumperInterface
     */
    protected function getRuleCollectionDumperInstance(RuleCollection $rules)
    {
        return new $this->options['rule_collection_dumper_class']($rules);
    }

    public function registerExtension(ExtensionInterface $extension)
    {
        $this->expressionLanguage->registerExtension($extension);
    }

    public function evaluate($expression, array $values = array())
    {
        if ($expression instanceof Rule) {
            $expression = $expression->getExpression();
        }

        return $this->expressionLanguage->evaluate($expression, $values);
    }

    public function evaluateNamedRule($ruleName, array $values = array())
    {
        $rule = $this->getRuleCollection()->get($ruleName);

        if ($rule instanceof Rule === false) {
            throw new \InvalidArgumentException(sprintf('No rule named "%s" found in rules', $ruleName));
        }

        return $this->evaluate($rule, $values);
    }
}