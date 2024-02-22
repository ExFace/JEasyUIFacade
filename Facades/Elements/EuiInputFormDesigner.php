<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryInputTrait;
use exface\Core\Facades\AbstractAjaxFacade\Elements\SurveyJsTrait;

/**
 * 
 * @author andrej.kabachnik
 *
 */
class EuiInputFormDesigner extends EuiInput
{
    use JqueryInputTrait;
    
    use SurveyJsTrait;
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildHtml()
     */
    function buildHtml()
    {
        return "<div id=\"{$this->getId()}\"></div>";
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJs()
     */
    function buildJs()
    {
        if ($initVal = $this->getWidget()->getValue()) {
            $initValJs = $this->buildJsValueSetter($this->escapeString($initVal, true, false));
        }
        return <<<JS

        (function(){
            {$this->buildJsCreatorSetup()}
                
            var oOptions = {};
            {$this->buildJsCreatorOptions('oOptions')}
            oCreator = new SurveyCreator.SurveyCreator("{$this->getIdOfCreatorDiv()}", oOptions);
            {$this->buildJsCreatorInit('oCreator')}
            {$this->buildJsCreatorVar()} = oCreator;
            
            {$initValJs};
        })();
        {$this->buildJsEventScripts()}
JS;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInputForm::buildJsSurveyConfigGetter()
     */
    protected function buildJsSurveyModelGetter() : string
    {
        return $this->buildJsValueGetter();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJsValidator()
     */
    public function buildJsValidator(?string $valJs = null) : string
    {
        // TODO
        return "true";
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsValueSetter()
     */
    public function buildJsValueSetter($value)
    {
        return $this->buildJsCreatorValueSetter($value);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsValueGetter()
     */
    public function buildJsValueGetter()
    {
        return $this->buildJsCreatorValueGetter();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJsSetDisabled()
     */
    public function buildJsSetDisabled(bool $trueOrFalse) : string
    {
        // TODO
        return '';
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildHtmlHeadTags()
     */
    public function buildHtmlHeadTags()
    {
        return $this->buildHtmlHeadTagsForSurvey();
    }
    
    protected function buildJsSurveyTheme() : string
    {
        return '"default"';
    }
    
    protected function getIdOfSurveyDiv() : string
    {
        return $this->getId();
    }
    
    protected function getIdOfCreatorDiv() : string
    {
        return $this->getId();
    }
}