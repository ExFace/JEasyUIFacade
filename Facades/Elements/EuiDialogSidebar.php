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
        $title = $this->getCaption();
        if ($title !== null && trim($title) === '') {
            $title = null;
        }

        // Width
        if ($widthCss = $this->buildCssWidth()) {
            $style .= "width: {$widthCss};";
        }

        // Collapsible
        if ($widget->isCollapsible()) {
            $options .= "collapsible: true,";
            if ($widget->isCollapsed()) {
                $options .= "collapsed: true,";
            }
            // Make sure, there is always a title if the sidebar is collapsible because it will
            // need its header to house the collapse-button
            if ($title === null) {
                $title = '';
            }
        } else {
            $options .= "collapsible: false,";
        }
        if ($title !== null) {
            $options .= "title: '{$this->escapeString($title, false, true)}',";
        }
        
        if ($widget->isResizable()) {
            $options .= "split: true,";
        }
        if (null !== $this->getOnResizeScript()) {
            $options .= "onResize: {$this->buildJsFunctionPrefix()}_onResize, ";
        }

        return <<<HTML

                    <div data-options="region:'east', {$options}" class="{$this->buildCssElementClass()}" style="{$style}">
                        {$this->buildHtmlForChildren()}
                    </div>
HTML;
    }   

    public function buildJs()
    {
        $js = parent::buildJs();
        
        if (null !== $script = $this->getOnResizeScript()) {
            $js .= <<<JS

function {$this->buildJsFunctionPrefix()}_onResize(){
    $script
}
JS;
        }

        return $js;
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