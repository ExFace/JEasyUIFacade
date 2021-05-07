<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Interfaces\Actions\ActionInterface;

class EuiInputCustom extends EuiInput
{

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::init()
     */
    protected function init()
    {
        parent::init();
        $this->setElementType('div');
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildHtml()
     */
    public function buildHtml()
    {
        return $this->buildHtmlLabelWrapper($this->getWidget()->getHtml() ?? '');
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
            $initPropsJs .= ($this->getWidget()->getScriptToSetValue(json_encode($value)) ?? '');
        }
        if ($this->getWidget()->isDisabled()) {
            $initPropsJs .= $this->buildJsDisabler();
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
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJsValueSetterMethod()
     */
    public function buildJsValueSetter($value)
    {
        return $this->getWidget()->getScriptToSetValue($value) ?? '';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsValueGetter()
     */
    public function buildJsValueGetter()
    {
        return $this->getWidget()->getScriptToGetValue() ?? '';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJsValidator()
     */
    public function buildJsValidator()
    {
        return $this->getWidget()->getScriptToValidateInput() ?? $this->buildJsValidatorViaTrait();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJsEnabler()
     */
    public function buildJsEnabler()
    {
        return $this->getWidget()->getScriptToEnable() ?? parent::buildJsEnabler();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJsDisabler()
     */
    public function buildJsDisabler()
    {
        return $this->getWidget()->getScriptToDisable() ?? parent::buildJsDisabler();
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
        $this->getWidget()->getScriptToAttachOnChange($this->getOnChangeScript()) ?? '';
    }
}