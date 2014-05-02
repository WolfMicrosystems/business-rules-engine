<?php
namespace WMS\Library\BusinessRulesEngine\ExpressionLanguage\Extension;

interface ExtensionInterface
{
    /**
     * @return array
     */
    public function getGlobals();

    /**
     * @return array
     */
    public function getFunctions();

    /**
     * @return string
     */
    public function getName();
}