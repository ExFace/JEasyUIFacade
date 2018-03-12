<?php
namespace exface\JEasyUiTemplate\Template\Elements;

class euiCheckBox extends euiInput
{

    protected function init()
    {
        $this->setElementType('checkbox');
    }

    function buildHtml()
    {
        $output = '	<div style="width: calc(100% + 2px); height: 100%; display: inline-block; text-align:left;">
						<input type="checkbox" value="1" 
								id="' . $this->getId() . '_checkbox"
								onchange="$(\'#' . $this->getId() . '\').val(this.checked);"' . '
								' . ($this->getValueWithDefaults() ? 'checked="checked" ' : '') . '
								' . ($this->getWidget()->isDisabled() ? 'disabled="disabled"' : '') . ' />
						<input type="hidden" name="' . $this->getWidget()->getAttributeAlias() . '" id="' . $this->getId() . '" value="' . $this->getValueWithDefaults() . '" />
					</div>';
        return $this->buildHtmlLabelWrapper($output);
    }

    public function buildJs()
    {
        return $this->buildJsEventScripts();
    }

    public function buildJsValueGetter()
    {
        return '$("#' . $this->getId() . '_checkbox").' . $this->buildJsValueGetterMethod();
    }

    public function buildJsValueGetterMethod()
    {
        return 'prop(\'checked\')';
    }

    function buildJsValueSetter($value)
    {
        return '($("#' . $this->getId() . '_checkbox").' . $this->buildJsValueSetterMethod($value);
    }

    function buildJsValueSetterMethod($value)
    {
        return 'prop(\'checked\', ' . $value . ').trigger("change")';
    }

    function buildJsInitOptions()
    {
        $options = 'on: "1"' . ', off: "0"' . ($this->getWidget()->isDisabled() ? ', disabled: true' : '');
        return $options;
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
?>