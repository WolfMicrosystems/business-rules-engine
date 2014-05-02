<?php
namespace WMS\Library\BusinessRulesEngine\Dumper;

use WMS\Library\BusinessRulesEngine\RuleCollection;

/**
 * RuleCollectionDumperInterface is the interface that all rule collection dumper classes must implement.
 * @author Andrew Moore <me@andrewmoore.ca>
 */
interface RuleCollectionDumperInterface
{
    /**
     * Dumps a set of rules to a string representation of executable code
     * that can then be evaluated.
     *
     * @param array $options An array of options
     *
     * @return string Executable code
     */
    public function dump(array $options = array());

    /**
     * Gets the rules to dump.
     *
     * @return RuleCollection A RuleCollection instance
     */
    public function getRules();
}