<?php
namespace WMS\Library\BusinessRulesEngine\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use WMS\Library\BusinessRulesEngine\ExpressionLanguage\Extension\ExtensionInterface;

class ExtensibleExpressionLanguage extends ExpressionLanguage
{
    /** @var ExtensionInterface[] */
    protected $extensions = array();

    public function registerExtension(ExtensionInterface $extension)
    {
        if (isset($this->extensions[$extension->getName()])) {
            // Extension is already registered
            return;
        }

        $extensionFunctions = $extension->getFunctions();

        if (is_array($extensionFunctions) || $extensionFunctions instanceof \Iterator) {
            foreach ($extensionFunctions as $functionName => $callbackRef) {
                $this->register(
                    $functionName,
                    function () use ($extension, $functionName, $callbackRef) {
                        $arguments = func_get_args();

                        $sTokens = implode(', ', array_fill(0, count($arguments), '%s'));

                        return '$wmsExtensibleExpressionLanguage->getExtension(' . var_export($extension->getName(), true) . ')->' . $functionName . vsprintf('(' . $sTokens . ')', $arguments);
                    },
                    function () use ($extension, $callbackRef) {
                        $arguments = func_get_args();
                        array_shift($arguments); // Lose the first argument (unused)

                        return call_user_func_array(array($extension, $callbackRef), $arguments);
                    }
                );
            }
        }


        $this->extensions[$extension->getName()] = $extension;
    }

    public function evaluate($expression, $values = array())
    {
        $mergedValues = array_merge($this->getExtensionGlobals(), $values);
        return parent::evaluate($expression, $mergedValues);
    }

    public function compile($expression, $names = array())
    {
        $mergedNames = array_unique(array_merge(array_keys($this->getExtensionGlobals()), $names));
        return parent::compile($expression, $mergedNames);
    }

    public function parse($expression, $names)
    {
        $mergedNames = array_unique(array_merge(array_keys($this->getExtensionGlobals()), $names));
        return parent::parse($expression, $mergedNames);
    }

    protected function getExtensionGlobals()
    {
        $globals = array(
            'wmsExtensibleExpressionLanguage' => $this // Base global added to all expressions
        );

        foreach ($this->extensions as $extension) {
            $extensionGlobals = $extension->getGlobals();

            if (is_array($extensionGlobals) || $extensionGlobals instanceof \Iterator) {
                foreach ($extensionGlobals as $name => $value) {
                    if (isset($globals[$name])) {
                        continue;
                    }

                    $globals[$name] = $value;
                }
            }
        }

        return $globals;
    }
} 