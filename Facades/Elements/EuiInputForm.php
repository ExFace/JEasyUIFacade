<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryInputTrait;
use exface\Core\Facades\AbstractAjaxFacade\Elements\SurveyJsTrait;

class EuiInputForm extends EuiInput
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
        return $this->buildHtmlLabelWrapper("<div id=\"{$this->getId()}\"></div>");
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJs()
     */
    function buildJs()
    {
        $initVal = $this->getWidget()->getValueWithDefaults();
        if (empty($initVal)) {
            $initVal = '{}';
        }
        
        return <<<JS

        (function(){
            var oSurvey;
            {$this->buildJsSurveySetup()}
            $('#{$this->getId()}').data('survey-model', {$this->getWidget()->getFormConfig()});
            {$this->buildJsValueSetter($this->escapeString($initVal, true, false))};
        })();
        {$this->buildJsEventScripts()}
JS;
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsSurveyModelGetter() : string
    {
        return "$('#{$this->getId()}').data('survey-model')";
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