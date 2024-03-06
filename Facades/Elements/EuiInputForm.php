<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryInputTrait;
use exface\Core\Facades\AbstractAjaxFacade\Elements\SurveyJsTrait;
use exface\Core\DataTypes\StringDataType;
use exface\Core\CommonLogic\DataSheets\DataColumn;

class EuiInputForm extends EuiInput
{
    use JqueryInputTrait;
    
    use SurveyJsTrait;
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildHtml()
     */
    public function buildHtml()
    {
        return $this->buildHtmlLabelWrapper("<div id=\"{$this->getId()}\"></div>");
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJs()
     */
    public function buildJs()
    {
        $widget = $this->getWidget();
        $initVal = $widget->getValueWithDefaults();
        if (empty($initVal)) {
            $initVal = '{}';
        }
        
        switch (true) {
            case $widget->isFormConfigBoundToAttribute():
                $formConfigJs = '{}';
                break;
            case $widget->isFormConfigBoundByReference():
                $link = $widget->getFormConfigExpression()->getWidgetLink($widget);
                $formConfigJs = $this->getFacade()->getElement($link->getTargetWidget())->buildJsValueGetter();
                break;
            default: 
                $formConfigJs = $widget->getFormConfig() ?? '{}';
        }
        
        return <<<JS

        (function(){
            var oSurvey;
            {$this->buildJsSurveySetup()}
            $('#{$this->getId()}').data('survey-model', {$formConfigJs});
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
     * @param string $valueJs
     * @return string
     */
    protected function buildJsSurveyModelSetter(string $valueJs) : string
    {
        return <<<JS
(function(oModel) {
            var oValue = {$this->buildJsValueGetter()};
            $('#{$this->getId()}').data('survey-model', oModel);
            {$this->buildJsValueSetter('oValue')};
        })({$valueJs})
JS;
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
    
    /**
     * 
     * @return string
     */
    protected function buildJsSurveyTheme() : string
    {
        return '"default"';
    }
    
    /**
     * 
     * @return string
     */
    protected function getIdOfSurveyDiv() : string
    {
        return $this->getId();
    }
    
    /**
     * 
     * @return string
     */
    protected function getIdOfCreatorDiv() : string
    {
        return $this->getId();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::registerConditionalPropertiesLiveRefs()
     */
    protected function registerConditionalPropertiesLiveRefs()
    {
        parent::registerConditionalPropertiesLiveRefs();
        $this->registerSurveyLiveConfigAtLinkedElement();
        return;
    }
}