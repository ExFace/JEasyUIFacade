<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\DataTypes\BooleanDataType;

class EuiInputCheckBox extends EuiInput
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::getElementType()
     */
    public function getElementType() : ?string
    {
        return 'checkbox';
    }
    
    public function buildHtml()
    {
        $output = '	<div style="width: calc(100% + 2px); height: 100%; display: inline-block; text-align:left;">
						<input type="checkbox" value="1" 
								id="' . $this->getId() . '_checkbox"
								onchange="$(\'#' . $this->getId() . '\').val(this.checked).change();"' . '
								' . (BooleanDataType::cast($this->getWidget()->getValueWithDefaults()) ? 'checked="checked" ' : '') . '
								' . ($this->getWidget()->isDisabled() ? 'disabled="disabled"' : '') . ' />
						<input type="hidden" name="' . $this->getWidget()->getAttributeAlias() . '" id="' . $this->getId() . '" value="' . $this->getWidget()->getValueWithDefaults() . '" />
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
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsValidator()
     */
    public function buildJsValidator(string $valJs = null)
    {
        return 'true';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJsSetDisabled()
     */
    public function buildJsSetDisabled(bool $trueOrFalse, bool $resetWidgetOnChange = false) : string
    {
        if ($trueOrFalse === true) {
            return '$("#' . $this->getId() . '_checkbox").attr("disabled", true)';
        } else {
            return '$("#' . $this->getId() . '_checkbox").attr("disabled", false)';
        }
    }
}