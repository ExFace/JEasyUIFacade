<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JsInputCustomTrait;
use exface\Core\Interfaces\Actions\ActionInterface;

/**
 * @method \exface\Core\Widgets\InputCustom getWidget()
 */
class EuiInputCustom extends EuiInput
{
    use JsInputCustomTrait;
    
    protected function init()
    {
        parent::init();
        $widget = $this->getWidget();
        if ($widget->hasParent() && null !== $js = $this->getWidget()->getScriptToResize()) {
            $this->getFacade()->getElement($this->getWidget()->getParent())->addOnResizeScript($js);
        }
    }

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
            $initPropsJs .= $this->getWidget()->getScriptToSetValue($this->escapeString($value, true, false) ?? '') . ';';
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

setTimeout(function(){
    {$this->getWidget()->getScriptToResize()}
}, 100);

JS;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildHtmlHeadTags()
     */
    public function buildHtmlHeadTags()
    {
        $widget = $this->getWidget();
        $tags = array_merge(parent::buildHtmlHeadTags(), $widget->getHtmlHeadTags());
        if ($customCss = $widget->getCss()) {
            $tags[] = <<<HTML

<style type="text/css">
{$customCss}
</style>
HTML;
        }
        return $tags;
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
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::getElementType()
     */
    public function getElementType() : ?string
    {
        return null;
    }

    protected function registerLiveReferenceAtLinkedElement()
    {
        parent::registerLiveReferenceAtLinkedElement();
        $this->registerLiveReferencesAtCustomLinks();
    }
}