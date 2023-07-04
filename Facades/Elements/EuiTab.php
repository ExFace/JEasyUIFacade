<?php
namespace exface\JEasyUIFacade\Facades\Elements;

/**
 *
 * @author Andrej Kabachnik
 *        
 * @method exface\Core\Widgets\Tab getWidget()
 */
class EuiTab extends EuiPanel
{    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiText::init()
     */
    protected function init()
    {
        parent::init();
        
        // Register an onChange-Script on the element linked by a disable condition and similar things.
        $this->registerConditionalPropertiesLiveRefs();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiWidgetGrid::buildHtml()
     */
    public function buildHtml()
    {
        $widget = $this->getWidget();
        
        $children_html = <<<HTML

            {$this->buildHtmlForWidgets()}
            <div id="{$this->getId()}_sizer" style="width:calc(100%/{$this->getNumberOfColumns()});min-width:{$this->getMinWidth()};"></div>
HTML;
        
        // Wrap children widgets with a grid for masonry layouting - but only if there is something to be layed out
        if ($widget->countWidgetsVisible() > 1) {
            // masonry_grid-wrapper wird benoetigt, da die Groesse des Tabs selbst nicht
            // veraendert werden soll.
            $children_html = <<<HTML

        <div class="grid" id="{$this->getId()}_masonry_grid" style="width:100%;height:100%;">
            {$children_html}
        </div>
HTML;
        }
        
        $title = $widget->getHideCaption() ? '' : ' title="' . str_replace('"', "'", $widget->getCaption()) . '"';
        
        
        $output = <<<HTML

    <div {$title} id="{$this->getId()}" data-options="{$this->buildJsDataOptions()}">
        {$children_html}
    </div>
HTML;
        return $output;
    }
    
    public function buildJs()
    {
        return parent::buildJs() . <<<JS
        
        {$this->buildJsEventScripts()}
JS;
    }
    
    /**
     * Returns JS scripts for event handling like live references, onChange-handlers,
     * disable conditions, etc.
     *
     * @return string
     */
    protected function buildJsEventScripts()
    {
        if ($activeIf = $this->getWidget()->getActiveIf()) {
            $activeIfInit = $this->buildJsConditionalProperty($activeIf, $this->buildJsSetActive(true), $this->buildJsSetActive(false), true);
        } else {
            $activeIfInit = '';
        }
        
        return <<<JS

    $(function() {
        {$this->buildjsConditionalProperties(true)}
        {$activeIfInit}
    });
JS;
    }
    

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiPanel::buildJsDataOptions()
     */
    public function buildJsDataOptions()
    {
        $widget = $this->getWidget();
        
        $output = parent::buildJsDataOptions() . ($widget->isHidden() || $widget->isDisabled() ? ', disabled:true' : '');
        return $output;
    }
    
    protected function getFitOption() : bool
    {
        return false;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiPanel::buildJsLayouterFunction()
     */
    protected function buildJsLayouterFunction() : string
    {
        $output = <<<JS

    function {$this->buildJsFunctionPrefix()}layouter() {
        if (!$("#{$this->getId()}_masonry_grid").data("masonry")) {
            if ($("#{$this->getId()}_masonry_grid").find(".{$this->getId()}_masonry_exf-grid-item").length > 0) {
                $("#{$this->getId()}_masonry_grid").masonry({
                    columnWidth: "#{$this->getId()}_sizer",
                    itemSelector: ".{$this->getId()}_masonry_exf-grid-item",
                    transitionDuration: 0
                });
            }
        } else {
            $("#{$this->getId()}_masonry_grid").masonry("reloadItems");
            $("#{$this->getId()}_masonry_grid").masonry();
        }
    }
JS;
        
        return $output;
    }

    /**
     * The default column number for tabs is defined for the tabs widget or its derivatives.
     *
     * @return integer
     */
    public function getNumberOfColumnsByDefault() : int
    {
        $parent_element = $this->getFacade()->getElement($this->getWidget()->getParent());
        if (method_exists($parent_element, 'getNumberOfColumnsByDefault')) {
            return $parent_element->getNumberOfColumnsByDefault();
        }
        return parent::getNumberOfColumnsByDefault();
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsSetDisabled()
     */
    public function buildJsSetDisabled(bool $trueOrFalse) : string
    {
        $widget = $this->getWidget();
        if ($trueOrFalse === true) {
            return "$('#{$this->getFacade()->getElement($widget->getParent())->getId()}').tabs('disableTab', {$widget->getTabIndex()})";
        } else {
            return "$('#{$this->getFacade()->getElement($widget->getParent())->getId()}').tabs('enableTab', {$widget->getTabIndex()})";
        }
    }
    
    /**
     * 
     * @param bool $trueOrFalse
     * @return string
     */
    public function buildJsSetActive(bool $trueOrFalse) : string
    {
        $widget = $this->getWidget();
        $op = $trueOrFalse === true ? 'select' : 'unselect';
        return "$('#{$this->getFacade()->getElement($widget->getParent())->getId()}').tabs('{$op}', {$widget->getTabIndex()})";
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::registerConditionalPropertiesLiveRefs()
     */
    protected function registerConditionalPropertiesLiveRefs()
    {
        parent::registerConditionalPropertiesLiveRefs();
        
        if ($activeIf = $this->getWidget()->getActiveIf()) {
            $this->registerConditionalPropertyUpdaterOnLinkedElements($activeIf, $this->buildJsSetActive(true), $this->buildJsSetActive(false));
        }
        
        return;
    }
}