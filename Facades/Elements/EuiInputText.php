<?php
namespace exface\JEasyUIFacade\Facades\Elements;

class EuiInputText extends EuiInput
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::getElementType()
     */
    public function getElementType() : ?string
    {
        return 'textbox';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildCssHeightDefaultValue()
     */
    protected function buildCssHeightDefaultValue()
    {
        return ($this->getHeightRelativeUnit() * 2) . 'px';
    }

    public function buildHtml()
    {
        $output = ' <textarea 
							name="' . $this->getWidget()->getAttributeAlias() . '" 
							id="' . $this->getId() . '"
							' . ($this->getWidget()->isRequired() ? 'required="true" ' : '') . '
							' . ($this->getWidget()->isDisabled() ? 'disabled="disabled" ' : '') . '>' . $this->getWidget()->getValue() . '</textarea>
					';
        return $this->buildHtmlLabelWrapper($output);
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJs()
     */
    public function buildJs()
    {
        return $this->buildJsEventScripts();
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJsValueSetterMethod()
     */
    public function buildJsValueSetterMethod($value)
    {
        return 'val(' . $value . ').trigger("change")';
    }
    
    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsValidator()
     */
    public function buildJsValidator(string $valJs = null)
    {
        return $this->buildJsValidatorViaTrait($valJs);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJsSetDisabled()
     */
    public function buildJsSetDisabled(bool $trueOrFalse, bool $resetWidgetOnChange = false) : string
    {
        if ($trueOrFalse === true) {
            return '$("#' . $this->getId() . '").attr("disabled", "disabled")';
        } else {
            return '$("#' . $this->getId() . '").removeAttr("disabled")';
        }
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiValue::buildCssElementClass()
     */
    public function buildCssElementClass()
    {
        return parent::buildCssElementClass() . ' exf-inputtext';
    }
    
    /**
     * javascript to get if an input is required or not, must not end with a semicolon!
     *
     * @return string
     */
    protected function buildJsRequiredGetter() : string
    {
        return "($('#{$this->getId()}').prop('required') != undefined)";
    }
    
    /**
     *
     * @param bool $required
     * @return string
     */
    protected function buildJsSetRequired(bool $required) : string
    {
        if ($required === true) {
            return "$('#{$this->getId()}').prop('required', 'required');";
        } else {
            return "$('#{$this->getId()}').removeProp('required');";
        }
    }
}