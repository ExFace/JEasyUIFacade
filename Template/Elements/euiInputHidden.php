<?php
namespace exface\JEasyUiTemplate\Template\Elements;

class euiInputHidden extends euiInput
{

    protected function init()
    {
        parent::init();
        $this->setElementType('hidden');
    }

    function generateHtml()
    {
        $output = '<input type="hidden" 
								name="' . $this->getWidget()->getAttributeAlias() . '" 
								value="' . $this->getValueWithDefaults() . '" 
								id="' . $this->getId() . '" />';
        return $output;
    }

    function generateJs()
    {
        $output .= $this->buildJsEventScripts();
        return $output;
    }

    function buildJsValueSetterMethod($value)
    {
        return 'val(' . $value . ').trigger("change")';
    }

    function buildJsValueGetterMethod()
    {
        return 'val()';
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJsValidator()
     */
    function buildJsValidator()
    {
        return 'true';
    }
}