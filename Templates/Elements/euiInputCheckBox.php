<?php
namespace exface\JEasyUiTemplate\Templates\Elements;

class euiInputCheckBox extends euiInput
{

    public function getElementType()
    {
        return 'checkbox';
    }
    
    public function buildHtml()
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

    public function buildJsValueSetter($value)
    {
        return '$("#' . $this->getId() . '_checkbox").' . $this->buildJsValueSetterMethod($value);
    }

    public function buildJsValueSetterMethod($value)
    {
        return 'prop(\'checked\', ' . $value . ').trigger("change")';
    }

    public function buildJsInitOptions()
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
    public function buildJsValidator()
    {
        return 'true';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiInput::buildJsDisabler()
     */
    public function buildJsDisabler()
    {
        return '$("#' . $this->getId() . '_checkbox").attr("disabled", true)';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiInput::buildJsEnabler()
     */
    public function buildJsEnabler()
    {
        return '$("#' . $this->getId() . '_checkbox").attr("disabled", false)';
    }
}