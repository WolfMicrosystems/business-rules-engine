<?php
namespace WMS\Library\BusinessRulesEngine;

use Symfony\Component\Config\Resource\ResourceInterface;

/**
 * Collection holding multiple Rule instances
 *
 * @author Andrew Moore <me@andrewmoore.ca>
 */
class RuleCollection implements \IteratorAggregate, \Countable
{
    /**
     * @var ResourceInterface[]
     */
    private $resources = array();

    /**
     * @var Rule[]
     */
    private $rules = array();

    public function __clone()
    {
        foreach ($this->rules as $name => $rule) {
            $this->rules[$name] = clone $rule;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->rules);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->rules);
    }

    /**
     * Adds a rule.
     *
     * @param string $name The rule name
     * @param Rule   $rule A Rule instance
     */
    public function add($name, Rule $rule)
    {
        unset($this->rules[$name]);

        $this->rules[$name] = $rule;
    }

    /**
     * Returns all rules in this collection.
     *
     * @return Rule[] An array of rules
     */
    public function all()
    {
        return $this->rules;
    }

    /**
     * Gets a rule by name.
     *
     * @param string $name The rule name
     *
     * @return Rule|null A Rule instance or null when not found
     */
    public function get($name)
    {
        return isset($this->rules[$name]) ? $this->rules[$name] : null;
    }

    /**
     * Removes a rule or an array of rules by name from the collection
     *
     * @param string|array $name The rule name or an array of rule names
     */
    public function remove($name)
    {
        foreach ((array)$name as $n) {
            unset($this->rules[$n]);
        }
    }

    /**
     * Adds a tag for all rules.
     *
     * @param string $name       The tag name
     * @param array  $attributes An array of attributes
     */
    public function addTag($name, array $attributes = array())
    {
        foreach ($this->rules as $rule) {
            $rule->addTag($name, $attributes);
        }
    }

    /**
     * Clears all tags for a given name in all Rules of this collection.
     *
     * @param string $name The tag name
     *
     * @return Rule
     */
    public function clearTag($name)
    {
        foreach ($this->rules as $rule) {
            $rule->clearTag($name);
        }
    }

    /**
     * Clears the tags for all rules.
     *
     * @return Rule The current instance
     *
     * @api
     */
    public function clearTags()
    {
        foreach ($this->rules as $rule) {
            $rule->clearTags();
        }
    }

    /**
     * Adds a rule collection at the end of the current set by appending all
     * rules of the added collection.
     *
     * @param RuleCollection $collection A RuleCollection instance
     */
    public function addCollection(RuleCollection $collection)
    {
        // we need to remove all rules with the same names first because just replacing them
        // would not place the new rule at the end of the merged array
        foreach ($collection->all() as $name => $rule) {
            unset($this->rules[$name]);
            $this->rules[$name] = $rule;
        }

        $this->resources = array_merge($this->resources, $collection->getResources());
    }

    public function findTaggedRules($tagName)
    {
        $taggedCollection = new RuleCollection();

        foreach ($this->rules as $name => $rule) {
            if ($rule->hasTag($tagName)) {
                $taggedCollection->add($name, clone $rule);
            }
        }

        return $taggedCollection;
    }

    /**
     * Returns an array of resources loaded to build this collection.
     *
     * @return ResourceInterface[] An array of resources
     */
    public function getResources()
    {
        return array_unique($this->resources);
    }

    /**
     * Adds a resource for this collection.
     *
     * @param ResourceInterface $resource A resource instance
     */
    public function addResource(ResourceInterface $resource)
    {
        $this->resources[] = $resource;
    }
}