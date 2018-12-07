<?php
namespace exface\JEasyUiTemplate\Templates\Elements;

use exface\Core\Widgets\SplitPanel;

/**
 *
 * @method SplitPanel getWidget()
 * @author aka
 *        
 */
class euiSplitPanel extends euiPanel
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
            $height = 'calc( ' . $height . ' + 7px)';
        }
        if ($width && ! $this->getWidget()->getWidth()->isPercentual()) {
            $width = 'calc( ' . $width . ' + 7px)';
        }
        
        $style = ($height ? 'height: ' . $height . ';' : '') . ($width ? 'width: ' . $width . ';' : '');
        
        $children_html = <<<HTML

                        {$this->buildHtmlForChildren()}
                        <div id="{$this->getId()}_sizer" style="width:calc(100% / {$this->getNumberOfColumns()});min-width:{$this->getMinWidth()};"></div>
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

                <div id="{$this->getId()}" data-options="{$this->buildJsDataOptions()}" style="{$style}">
                    {$children_html}
                </div>
HTML;
        
        return $output;
    }

    public function buildJsDataOptions()
    {
        /* @var $widget \exface\Core\Widgets\LayoutPanel */
        $widget = $this->getWidget();
        $output = parent::buildJsDataOptions();
        
        $output .= ($output ? ',' : '') . 'region:\'' . $this->getRegion() . '\'
					,title:\'' . $widget->getCaption() . '\'' . ($this->getRegion() !== 'center' ? ',split:' . ($widget->getResizable() ? 'true' : 'false') : '');
        
        return $output;
    }
    
    /**
     * Fit is never used as it would cause the panel to take up all the space
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiWidgetGrid::getFitOption()
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
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiPanel::buildJsLayouterFunction()
     */
    public function buildJsLayouterFunction()
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
}
?>