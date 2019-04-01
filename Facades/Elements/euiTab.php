<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\Tab;

/**
 *
 * @author Andrej Kabachnik
 *        
 * @method Tab getWidget()
 */
class EuiTab extends EuiPanel
{

    function buildHtml()
    {
        $widget = $this->getWidget();
        
        $children_html = <<<HTML

            {$this->buildHtmlForChildren()}
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

    <div {$title} data-options="{$this->buildJsDataOptions()}">
        {$children_html}
    </div>
HTML;
        return $output;
    }

    function buildJsDataOptions()
    {
        $widget = $this->getWidget();
        
        $output = parent::buildJsDataOptions() . ($widget->isHidden() || $widget->isDisabled() ? ', disabled:true' : '');
        return $output;
    }
    
    protected function getFitOption()
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
                    itemSelector: ".{$this->getId()}_masonry_exf-grid-item"
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
}
?>