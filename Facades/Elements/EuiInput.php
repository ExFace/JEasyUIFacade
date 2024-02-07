<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Interfaces\Actions\ActionInterface;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryInputValidationTrait;

/**
 *
 * @method exface\Core\Widgets\Input getWidget()
 *        
 * @author Andrej Kabachnik
 *        
 */
class EuiInput extends EuiValue
{
    use JqueryInputValidationTrait {
        buildJsValidator as buildJsValidatorViaTrait;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiText::init()
     */
    protected function init()
    {
        parent::init();
        
        // Register an onChange-Script on the element linked by a disable condition and similar thins.
        $this->registerConditionalPropertiesLiveRefs();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiValue::getElementType()
     */
    public function getElementType() : ?string
    {
        return 'textbox';
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiText::buildHtml()
     */
    public function buildHtml()
    {
        /* @var $widget \exface\Core\Widgets\Input */
        $widget = $this->getWidget();
        $val = $widget->getValueWithDefaults();
        $output = '	<input style="height: 100%; width: 100%;"
						name="' . $widget->getAttributeAlias() . '" 
						value="' . $this->escapeString($val, false, true) . '" 
						id="' . $this->getId() . '"  
						' . ($widget->isRequired() ? 'required="true" ' : '') . '
						' . ($widget->isDisabled() ? 'disabled="disabled" ' : '') . ' 
						/>
					';
        return $this->buildHtmlLabelWrapper($output);
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiText::buildJs()
     */
    public function buildJs()
    {
        $output = '';
        $output .= "
				$('#" . $this->getId() . "')." . $this->getElementType() . "(" . ($this->buildJsDataOptions() ? '{' . $this->buildJsDataOptions() . '}' : '') . ");//.textbox('addClearBtn', 'icon-clear');
				";
        $output .= $this->buildJsEventScripts();
        return $output;
    }
    
    /**
     * Returns JS scripts for event handling like live references, onChange-handlers,
     * disable conditions, etc.
     * 
     * @return string
     */
    protected function buildJsEventScripts()
    {
        $widget = $this->getWidget();
        $hideInitiallyIfNeeded = '';
        $getInitialValueFromLink = '';
        
        if ($widget->isHidden() === true) {
            $hideInitiallyIfNeeded = $this->buildJsSetHidden(true);
        }
        if ($widget->getValueWidgetLink() || (! $widget->hasValue() && $widget->getCalculationWidgetLink())) {
            $getInitialValueFromLink = <<<JS

    try {
        {$this->buildJsLiveReference()}
    } catch (e) {
        console.warn('Failed to update live reference: ' + e);
    }
JS;
        }
        
        $js = $this->buildsJsAddValidationType();
        return $js . <<<JS

    // Event scripts for {$this->getId()}
    $getInitialValueFromLink
    $(function() { 
        {$this->buildJsOnChangeHandler()}
        {$this->buildjsConditionalProperties()}
        {$hideInitiallyIfNeeded}
    });

JS;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::buildjsConditionalProperties()
     */
    protected function buildjsConditionalProperties(bool $async = false) : string
    {
        $js = parent::buildjsConditionalProperties($async);
        
        // required_if
        if ($propertyIf = $this->getWidget()->getRequiredIf()) {
            $js .= $this->buildJsConditionalProperty($propertyIf, $this->buildJsSetRequired(true), $this->buildJsSetRequired(false), $async);
        }
        
        return $js;
    }
    
    protected function buildsJsAddValidationType() : string
    {
        $js = '';
        if ($this->isValidationRequired()) {
            $js = <<<JS
    $.extend($.fn.validatebox.defaults.rules, {
        {$this->getValidationRuleName()}: {
            validator: function(currentValue) {
                return ({$this->buildJsValidatorViaTrait('currentValue')});
            },
            message: {$this->escapeString($this->getValidationErrorText())}
        }
    });

JS;
        }
        return $js;
    }
    
    protected function buildJsSetRequired(bool $required) : string
    {
        return "$('#{$this->getId()}').{$this->getElementType()}('require'," . ($required ? 'true' : 'false') . ");";
    }
    
    /**
     * javascript to get if an input is required or not, must not end with a semicolon!
     * 
     * @return string
     */
    protected function buildJsRequiredGetter() : string
    {
        $modelValJs = $this->getWidget()->isRequired() ? 'true' : 'false';
        return "(function(jqEl){return jqEl.data('{$this->getElementType()}') !== undefined ? jqEl.{$this->getElementType()}('options')['required'] || {$modelValJs} : {$modelValJs};}($('#{$this->getId()}')))";
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::buildJsInitOptions()
     */
    public function buildJsInitOptions()
    {
        return $this->buildJsDataOptions();
    }

    /**
     * 
     * @return string
     */
    protected function buildJsDataOptions()
    {
        $options = '';
        
        if ($this->getOnChangeScript()) {
            $options .= "\n" . 'onChange: function(newValue, oldValue) {$(this).trigger("change");}';
        }
        
        if ($this->isValidationRequired()) {
            $options .= ($options ? ',' : '') . "\n validType: '{$this->getValidationRuleName()}'";
        }
        
        return $options;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsValueSetterMethod()
     */
    public function buildJsValueSetterMethod($value)
    {
        return $this->getElementType() . '("setValue", ' . $value . ')';
    }

    /**
     * 
     * @return string
     */
    protected function buildJsOnChangeHandler()
    {
        if ($this->getOnChangeScript()) {
            return "$('#" . $this->getId() . "').change(function(event){" . $this->getOnChangeScript() . "});";
        } else {
            return '';
        }
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsDataGetter($action, $custom_body_js)
     */
    public function buildJsDataGetter(ActionInterface $action = null)
    {
        if ($this->getWidget()->isDisplayOnly()) {
            return '{}';
        } else {
            return parent::buildJsDataGetter($action);
        }
    }

    /**
     *
     * {@inheritdoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsValidator()
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryInputValidationTrait::buildJsValidator()
     */
    public function buildJsValidator(?string $valJs = null) : string
    {
        if ($valJs === null && $this->isValidationRequired() === true) {
            return "$('#{$this->getId()}').{$this->getElementType()}('isValid')";
        }
        
        return $this->buildJsValidatorViaTrait($valJs);
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsSetDisabled()
     */
    public function buildJsSetDisabled(bool $trueOrFalse) : string
    {
        if ($trueOrFalse === true) {
            return '$("#' . $this->getId() . '").' . $this->getElementType() . '("disable")';
        } else {
            return '$("#' . $this->getId() . '").' . $this->getElementType() . '("enable").' . $this->getElementType() . '("validate")';
        }
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiText::buildCssHeightDefaultValue()
     */
    protected function buildCssHeightDefaultValue()
    {
        return ($this->getHeightRelativeUnit() * 1) . 'px';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiValue::buildCssElementClass()
     */
    public function buildCssElementClass()
    {
        return parent::buildCssElementClass() . ' exf-input';
    }
    
    /**
     * 
     * @return string
     */
    protected function getValidationRuleName() : string
    {
        // For some reason validation rule names cannot contain numbers. This leads to very strange
        // side-effects. So we just replace numbers with letters here.
        return str_replace(
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], 
            ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'], 
            $this->getId()
        );
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::registerConditionalPropertiesLiveRefs()
     */
    protected function registerConditionalPropertiesLiveRefs()
    {
        parent::registerConditionalPropertiesLiveRefs();
        
        if ($requiredIf = $this->getWidget()->getRequiredIf()) {
            $this->registerConditionalPropertyUpdaterOnLinkedElements($requiredIf, $this->buildJsSetRequired(true), $this->buildJsSetRequired(false));
        }
        
        return;
    }
}