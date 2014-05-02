<?php
namespace WMS\Library\BusinessRulesEngine\Dumper;

/**
 * PhpRuleCollectionDumper creates a PHP class able representing a pre-populated rule collection.
 *
 * @author Andrew Moore <me@andrewmoore.ca>
 */
class PhpRuleCollectionDumper extends RuleCollectionDumper
{
    /**
     * Dumps a set of rules to a PHP class.
     *
     * Available options:
     *
     *  * class:      The class name
     *  * base_class: The base class name
     *
     * @param array $options An array of options
     *
     * @return string A PHP class representing the generator class
     *
     * @api
     */
    public function dump(array $options = array())
    {
        $options = array_merge(array(
            'class'      => 'ProjectRuleCollection',
            'base_class' => 'WMS\\Library\\BusinessRulesEngine\\RuleCollection',
        ), $options);

        return <<<EOF
<?php

use WMS\Library\BusinessRulesEngine\Rule;

/**
 * {$options['class']}
 *
 * This class has been auto-generated
 * by the WMS BusinessRulesEngine Library.
 */
class {$options['class']} extends {$options['base_class']}
{
    private static \$declaredRules = {$this->generateDeclaredRules()};

    /**
     * Constructor.
     */
    public function __construct()
    {
        foreach(self::\$declaredRules as \$ruleName => \$ruleInfo) {
            \$rule = new Rule(\$ruleInfo[0]);

            foreach(\$ruleInfo[1] as \$tagName => \$tagAttrs) {
                foreach(\$tagAttrs as \$tagAttrsInstance) {
                    \$rule->addTag(\$tagName, \$tagAttrsInstance);
                }
            }

            \$this->add(\$ruleName, \$rule);
        }
    }
}

EOF;
    }

    /**
     * Generates PHP code representing an array of defined rules
     * together with the routes properties (e.g. requirements).
     *
     * @return string PHP code
     */
    private function generateDeclaredRules()
    {
        $rules = "array(\n";
        foreach ($this->getRules()->all() as $name => $rule) {
            $properties = array();
            $properties[] = $rule->getExpression();
            $properties[] = $rule->getTags();

            $rules .= sprintf("        '%s' => %s,\n", $name, str_replace("\n", '', var_export($properties, true)));
        }
        $rules .= '    )';

        return $rules;
    }
}
