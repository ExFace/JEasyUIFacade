<?php
namespace exface\JEasyUIFacade\Facades\Elements;

/**
 *
 * @method exface\Core\Widgets\DialogSidebar getWidget()
 * @author Andrej Kabachnik
 *        
 */
class EuiDialogSidebar extends EuiWidgetGrid
{
    /**
     * Returns the HTML for a jEasyUI layout region panel
     * 
     * @link https://www.jeasyui.com/documentation/index.php > layout
     * 
     * @return string
     */
    public function buildHtmlLayoutRegion() : string
    {
        $widget = $this->getWidget();
        $options = '';
        $style = '';

        if ($widthCss = $this->buildCssWidth()) {
            $style .= "width: {$widthCss};";
        }
        if ($title = $this->getCaption()) {
            $options .= "title: '{$title}',";
        }
        if (! $widget->isCollapsible()) {
            $options .= "collapsible: false,";
        }
        if ($widget->isCollapsed()) {
            $options .= "collapsed: true,";
        }
        if ($widget->isResizable()) {
            $options .= "split: true,";
        }

        return <<<HTML

                    <div data-options="region:'east', {$options}" class="{$this->buildCssElementClass()}" style="{$style}">
                        {$this->buildHtmlForChildren()}
                    </div>
HTML;
    }   

    public function buildCssElementClass()
    {
        return 'exf-dialog-sidebar exf-element';
    }
    
    /**
     * 
     * @return int
     */
    public function getNumberOfColumnsByDefault() : int
    {
        return 1;
    }

    public function inheritsNumberOfColumns() : bool
    {
        return false;
    }
}