<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryInputTrait;

class EuiInputHidden extends EuiInput
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
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildHtml()
     */
    function buildHtml()
    {
        return $this->buildHtmlInput('hidden');
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJs()
     */
    function buildJs()
    {
        $output .= $this->buildJsEventScripts();
        return $output;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJsValueSetterMethod()
     */
    function buildJsValueSetterMethod($value)
    {
        return 'val(' . $value . ').trigger("change")';
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsValueGetterMethod()
     */
    function buildJsValueGetterMethod()
    {
        return 'val()';
    }
}