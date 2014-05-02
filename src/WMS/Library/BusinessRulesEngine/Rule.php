<?php
namespace WMS\Library\BusinessRulesEngine;

/**
 * Class used to represent a configured business rule
 *
 * @author Andrew Moore <me@andrewmoore.ca>
 */
class Rule
{
    /** @var string */
    private $expression;

    /** @var array */
    private $tags = array();

    /**
     * @param string $expression Rule's expression
     */
    function __construct($expression)
    {
        $this->setExpression($expression);
    }

    /**
     * @return string The rule's expression
     */
    public function getExpression()
    {
        return $this->expression;
    }

    /**
     * @param string $expression The rule's expression
     *
     * @return Rule The current instance
     */
    public function setExpression($expression)
    {
        $this->expression = $expression;
        return $this;
    }

    /**
     * Gets all tags.
     *
     * @return array An array of tags
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Gets a tag by name.
     *
     * @param string $name The tag name
     *
     * @return array An array of attributes
     */
    public function getTag($name)
    {
        return isset($this->tags[$name]) ? $this->tags[$name] : array();
    }

    /**
     * Adds a tag for this definition.
     *
     * @param string $name       The tag name
     * @param array  $attributes An array of attributes
     *
     * @return Rule The current instance
     *
     * @api
     */
    public function addTag($name, array $attributes = array())
    {
        $this->tags[$name][] = $attributes;

        return $this;
    }

    /**
     * Whether this rule has a tag with the given name
     *
     * @param string $name
     *
     * @return Boolean
     */
    public function hasTag($name)
    {
        return isset($this->tags[$name]);
    }

    /**
     * Clears all tags for a given name.
     *
     * @param string $name The tag name
     *
     * @return Rule
     */
    public function clearTag($name)
    {
        if (isset($this->tags[$name])) {
            unset($this->tags[$name]);
        }

        return $this;
    }

    /**
     * Clears the tags for this rule.
     *
     * @return Rule The current instance
     *
     * @api
     */
    public function clearTags()
    {
        $this->tags = array();

        return $this;
    }
} 