<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\SplitPanel;
use exface\Core\DataTypes\WidgetVisibilityDataType;

/**
 *
 * @method SplitPanel getWidget()
 * @author aka
 *        
 */
class EuiSplitPanel extends EuiPanel
{

    private $region = null;

    function buildHtml()
    {
        switch ($this->getRegion()) {
            case 'north':
            case 'south':
                $height = $this->getHeight();
                break;
            case 'east':
            case 'west':
                $width = $this->getWidth();
                break;
            case 'center':
                $height = $this->getHeight();
                $width = $this->getWidth();
                break;
        }
        
        if ($height && ! $this->getWidget()->getHeight()->isPercentual()) {
            $height = 'calc( ' . $height . ' + 10px)';
        }
        if ($width && ! $this->getWidget()->getWidth()->isPercentual()) {
            $width = 'calc( ' . $width . ' + 10px)';
        }
        
        $style = ($height ? 'height: ' . $height . ';' : '') . ($width ? 'width: ' . $width . ';' : '');
        
        $children_html = <<<HTML

                        {$this->buildHtmlForChildren()}
                        <div id="{$this->getId()}_sizer" style="height: 0px; width:calc(100% / {$this->getNumberOfColumns()});min-width:{$this->getMinWidth()};"></div>
HTML;
        
        // Wrap children widgets with a grid for masonry layouting - but only if there is something to be layed out
        if ($this->getWidget()->countWidgetsVisible() > 1) {
            $children_html = <<<HTML

                    <div class="grid" id="{$this->getId()}_masonry_grid" style="width:100%;height:100%;">
                        {$children_html}
                    </div>
HTML;
        }
        
        $output = <<<HTML

                <div id="{$this->getId()}" data-options="{$this->buildJsDataOptions()}" style="{$style}" class="{$this->buildCssElementClass()}">
                    {$children_html}
                </div>
HTML;
        
        return $output;
    }

    public function buildJsDataOptions()
    {
        /* @var $widget \exface\Core\Widgets\SplitPanel */
        $widget = $this->getWidget();
        $output = parent::buildJsDataOptions();
        
        $hint = $this->buildHintText($widget->getHint(), false);
        $title = $this->escapeString($this->getCaption(), false, true);
        if ($hint && $title) {
            if (strpos($hint, "'") !== false) {
                $hint = str_replace("'", "`", $hint);
            }
            $hint = $this->escapeString($hint, false, true);
            $hint = str_replace("\n", "\\n", $hint);
            $titleWithHint = "<span title=\'{$hint}\'>{$title}</span>";
        } else {
            $titleWithHint = $title;
        }
        $output .= ($output ? ',' : '') . 'region:\'' . $this->getRegion() . '\'
					,title:\'' . $titleWithHint . '\'' . ($this->getRegion() !== 'center' ? ',split:' . ($widget->getResizable() ? 'true' : 'false') : '');
        
        if ($this->getWidget()->getVisibility() >= WidgetVisibilityDataType::PROMOTED) {
            $output .= ($output ? ',' : '') . "headerCls:'promoted'";
        }
        
        return $output;
    }
    
    /**
     * Fit is never used as it would cause the panel to take up all the space
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiWidgetGrid::getFitOption()
     */
    protected function getFitOption() : bool
    {
        return false;
    }

    public function getRegion()
    {
        return $this->region;
    }

    public function setRegion($value)
    {
        $this->region = $value;
        return $this;
    }

    /**
     * 
     * {@inheritDoc}
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
    
    public function buildCssElementClass()
    {
        return parent::buildCssElementClass() . ' exf-splitpanel';
    }
}
?>