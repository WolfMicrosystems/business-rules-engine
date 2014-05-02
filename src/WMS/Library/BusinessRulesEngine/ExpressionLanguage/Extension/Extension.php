<?php
namespace WMS\Library\BusinessRulesEngine\ExpressionLanguage\Extension;

abstract class Extension implements ExtensionInterface
{
    /**
     * @return array
     */
    public function getGlobals()
    {
        return array();
    }

    /**
     * @return string[]
     */
    public function getFunctions()
    {
        return array();
    }
} 