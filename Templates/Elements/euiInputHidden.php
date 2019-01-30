<?php
namespace exface\JEasyUiTemplate\Templates\Elements;

use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryInputTrait;

class euiInputHidden extends euiInput
{
    use JqueryInputTrait;
    
    protected function init()
    {
        parent::init();
        $this->setElementType('hidden');
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiInput::buildHtml()
     */
    function buildHtml()
    {
        return $this->buildHtmlInput('hidden');
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiInput::buildJs()
     */
    function buildJs()
    {
        $output .= $this->buildJsEventScripts();
        return $output;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiInput::buildJsValueSetterMethod()
     */
    function buildJsValueSetterMethod($value)
    {
        return 'val(' . $value . ').trigger("change")';
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJsValueGetterMethod()
     */
    function buildJsValueGetterMethod()
    {
        return 'val()';
    }
}