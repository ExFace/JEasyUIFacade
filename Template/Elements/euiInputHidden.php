<?php
namespace exface\JEasyUiTemplate\Template\Elements;

class euiInputHidden extends euiInput
{

    protected function init()
    {
        parent::init();
        $this->setElementType('hidden');
    }

    function buildHtml()
    {
        $output = '<input type="hidden" 
								name="' . $this->getWidget()->getAttributeAlias() . '" 
								value="' . $this->getValueWithDefaults() . '" 
								id="' . $this->getId() . '" />';
        return $output;
    }

    function buildJs()
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
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJsValidator()
     */
    function buildJsValidator()
    {
        if ($this->getWidget()->isRequired()) {
            return '(' . $this->buildJsValueGetter() . ' === "" ? false : true)';
        } 
        return 'true';
    }
}