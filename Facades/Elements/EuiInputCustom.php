<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Interfaces\Actions\ActionInterface;

class EuiInputCustom extends EuiInput
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildHtml()
     */
    public function buildHtml()
    {
        return $this->buildHtmlLabelWrapper($this->getWidget()->getHtml() ?? "<div id=\"{$this->getId()}\"></div>");
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJs()
     */
    public function buildJs()
    {
        $scriptVars = '';
        foreach ($this->getWidget()->getScriptVariables() as $varName => $initVal) {
            $prefixedName = $this->buildJsFunctionPrefix() . $varName;
            $this->getWidget()->setScriptVariablePlaceholder($varName, $prefixedName);
            $scriptVars .= "var $prefixedName = $initVal;" . PHP_EOL;
        }
        
        $initJs = ($this->getWidget()->getScriptToInit() ?? '');
        
        $initPropsJs = '';
        if (($value = $this->getWidget()->getValueWithDefaults()) !== null) {
            $initPropsJs .= ($this->getWidget()->getScriptToSetValue(json_encode($this->escapeString($value))) ?? '');
        }
        if ($this->getWidget()->isDisabled()) {
            $initPropsJs .= $this->buildJsSetDisabled(true);
        }
        
        return <<<JS

$scriptVars

setTimeout(function(){
    {$initJs};
    {$initPropsJs};

    {$this->buildJsLiveReference()}
    {$this->buildJsOnChangeHandler()}
}, 0);

JS;
    }
    
    /**
     * 
     * @param string $widgetProperty
     * @param string $returnValueJs
     * @return string
     */
    protected function buildJsFallbackForEmptyScript(string $widgetProperty, string $returnValueJs = "''") : string
    {
        return "(function(){console.warn('Property {$widgetProperty} not set for widget InputCustom. Falling back to empty string'); return {$returnValueJs};})()";
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJsValueSetterMethod()
     */
    public function buildJsValueSetter($value)
    {
        return $this->getWidget()->getScriptToSetValue($value) ?? $this->buildJsFallbackForEmptyScript('script_to_set_value');
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsValueGetter()
     */
    public function buildJsValueGetter()
    {
        return $this->getWidget()->getScriptToGetValue() ?? $this->buildJsFallbackForEmptyScript('script_to_get_value');
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJsValidator()
     */
    public function buildJsValidator(?string $valJs = null) : string
    {
        return $this->getWidget()->getScriptToValidateInput() ?? $this->buildJsValidatorViaTrait($valJs);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJsSetDisabled()
     */
    public function buildJsSetDisabled(bool $trueOrFalse) : string
    {
        if ($trueOrFalse === true) {
            return $this->getWidget()->getScriptToDisable() ?? parent::buildJsSetDisabled($trueOrFalse);
        } else {
            return $this->getWidget()->getScriptToEnable() ?? parent::buildJsSetDisabled($trueOrFalse);
        }
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsDataGetter()
     */
    public function buildJsDataGetter(ActionInterface $action = null)
    {
        return $this->getWidget()->getScriptToGetData($action) ?? parent::buildJsDataGetter($action);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsDataSetter()
     */
    public function buildJsDataSetter(string $jsData) : string
    {
        return $this->getWidget()->getScriptToSetData($jsData) ?? parent::buildJsDataSetter($jsData);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildHtmlHeadTags()
     */
    public function buildHtmlHeadTags()
    {
        return array_merge(parent::buildHtmlHeadTags(), $this->getWidget()->getHtmlHeadTags());
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildCssElementClass()
     */
    public function buildCssElementClass()
    {
        return parent::buildCssElementClass() . ' exf-inputcustom ' . ($this->getWidget()->getCssClass() ?? '');
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJsOnChangeHandler()
     */
    protected function buildJsOnChangeHandler()
    {
        $this->getWidget()->getScriptToAttachOnChange($this->getOnChangeScript()) ?? $this->buildJsFallbackForEmptyScript('script_to_attach_on_change');
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::getElementType()
     */
    public function getElementType() : ?string
    {
        return 'div';
    }
}