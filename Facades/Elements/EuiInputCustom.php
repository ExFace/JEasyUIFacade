<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JsInputCustomTrait;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryToolbarsTrait;
use exface\Core\Interfaces\Widgets\iContainOtherWidgets;
use exface\Core\Interfaces\Widgets\iFillEntireContainer;

/**
 * Renders a custom input widget in jEasyUI
 * 
 * See [development documentation](../../Docs/Developer_docs/Widgets/InputCustom.md) for details.
 * 
 * @method \exface\Core\Widgets\InputCustom getWidget()
 */
class EuiInputCustom extends EuiInput
{
    use JsInputCustomTrait;
    use JqueryToolbarsTrait;
    
    protected function init()
    {
        parent::init();
        $widget = $this->getWidget();
        if ($widget->hasParent() && null !== $js = $this->getOnResizeScript()) {
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
        $widget = $this->getWidget();
        $html = $widget->getHtml() ?? "<div id=\"{$this->getId()}\"></div>";
        if (! $widget->hasToolbars()) {
            return $this->buildHtmlLabelWrapper($html);
        }

        return $this->buildHtmlGridItemWrapper($this->buildHtmlToolbarPanel($html), $this->getTooltip());
    }

    /**
     * Render a native panel title and place the table-style action bar above the custom content.
     */
    protected function buildHtmlToolbarPanel(string $html) : string
    {
        $fit = $this->getHeight() === 'auto' ? 'false' : 'true';
        // EasyUI reads the title attribute and inserts it as HTML.
        $title = htmlspecialchars(htmlspecialchars($this->getCaption(), ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');
        $toolbar = $this->buildHtmlToolbars();

        return <<<HTML
<div class="easyui-panel exf-inputcustom-panel" title="{$title}" data-options="fit:{$fit}">
    <div class="datatable-toolbar datagrid-toolbar">{$toolbar}</div>
    <div class="exf-inputcustom-content">{$html}</div>
</div>
HTML;
    }

    /**
     * Let a toolbar and its content determine the height when none is configured.
     */
    public function getHeight()
    {
        $widget = $this->getWidget();
        if ($widget->getHeight()->isUndefined() && ! ($widget instanceof iFillEntireContainer) && $widget->hasToolbars()) {
            return 'auto';
        }
        return parent::getHeight();
    }

    /**
     * Keep toolbar panels flush with their layout cell instead of using form-input padding.
     */
    public function getPadding($default = 0)
    {
        return $this->getWidget()->hasToolbars() ? '0' : parent::getPadding($default);
    }

    /**
     * Refit the EasyUI panel when the grid allocates a new height to the input.
     */
    protected function buildJsEuiSetHeigthMax(iContainOtherWidgets $containerWidget, string $onChangeHeightJs = '') : string
    {
        if ($this->getWidget()->hasToolbars()) {
            $onChangeHeightJs = <<<JS
surElem.children('.panel').children('.exf-inputcustom-panel.panel-body').panel('resize');
{$onChangeHeightJs}
JS;
        }
        return parent::buildJsEuiSetHeigthMax($containerWidget, $onChangeHeightJs);
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
        
        $toolbarJs = $this->getWidget()->hasToolbars() ? $this->buildJsToolbars() : '';

        return <<<JS

$scriptVars
$toolbarJs

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