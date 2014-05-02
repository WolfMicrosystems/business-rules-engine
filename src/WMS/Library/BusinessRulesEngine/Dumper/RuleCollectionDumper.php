<?php
namespace WMS\Library\BusinessRulesEngine\Dumper;

use WMS\Library\BusinessRulesEngine\RuleCollection;

/**
 * RuleCollectionDumper is the base class for all built-in rule collection dumpers.
 *
 * @author Andrew Moore <me@andrewmoore.ca>
 */
abstract class RuleCollectionDumper implements RuleCollectionDumperInterface
{
    /**
     * @var RuleCollection
     */
    private $rules;

    /**
     * Constructor.
     *
     * @param RuleCollection $rules The RuleCollection to dump
     */
    public function __construct(RuleCollection $rules)
    {
        $this->rules = $rules;
    }

    /**
     * {@inheritdoc}
     */
    public function getRules()
    {
        return $this->rules;
    }
}
