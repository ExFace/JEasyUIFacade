<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\MenuButton;
use exface\Core\Widgets\Tabs;

class EuiDialog extends EuiForm
{

    private $buttons_div_id = '';

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiPanel::init()
     */
    protected function init()
    {
        parent::init();
        $this->buttons_div_id = $this->getId() . '-buttons';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiWidgetGrid::getElementType()
     */
    public function getElementType() : ?string
    {
        return 'dialog';
    }
    
    /**
     *
     * @return boolean
     */
    public function isLazyLoading()
    {
        return $this->getWidget()->getLazyLoading(true);
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiForm::buildHtml()
     */
    public function buildHtml()
    {
        $widget = $this->getWidget();
        
        $children_html = '';
        if (($filler = $widget->getFillerWidget()) && ($alternative = $filler->getAlternativeContainerForOrphanedSiblings())) {
            $alternative->addWidget($widget->getMessageList(), 0);
            $messageListHtml = '';
        } else {
            $messageListHtml = $this->getFacade()->getElement($widget->getMessageList())->buildHtml();
        }
        
        $children_html = <<<HTML

                {$this->buildHtmlForWidgets()}
                <div id="{$this->getId()}_sizer" style="width:calc(100%/{$this->getNumberOfColumns()});min-width:{$this->getMinWidth()};"></div>
HTML;
            
        if ($widget->countWidgetsVisible() > 1) {
            // masonry_grid-wrapper wird benoetigt, da sonst die Groesse des Dialogs selbst
            // veraendert wird -> kein Scrollbalken.
            $children_html = <<<HTML
            
                <div class="grid exf-dialog" id="{$this->getId()}_masonry_grid" style="width:100%;height:100%;">
                    {$messageListHtml}
                    {$children_html}
                </div>
HTML;
        }

        $headerElem = $widget->hasHeader() ? $this->getFacade()->getElement($widget->getHeader()) : null;
        $sidebarElem = $widget->hasSidebar() ? $this->getFacade()->getElement($widget->getSidebar()) : null;
        
        if ($headerElem !== null || $sidebarElem !== null) {
            
            if ($headerElem && ! $this->isLayout()) {
                $children_html = <<<HTML
                
                <div style="height: 100%">
                    <div class="exf-dialog-header">
                        {$headerElem->buildHtml()}
                    </div>
                    {$children_html}
                </div>
                
HTML;
            } else {
                $headerHtml = $headerElem !== null ? $headerElem->buildHtmlLayoutRegion() : '';
                $sidebarHtml = $sidebarElem !== null ? $sidebarElem->buildHtmlLayoutRegion() : '';

                $children_html = <<<HTML
            
                <div class="easyui-layout" data-options="fit:true">
                    {$headerHtml}
                    <div data-options="region:'center'">
                        {$children_html}
                    </div>
                    {$sidebarHtml}
                </div>
            
HTML;
            }
        }
        
        $dialogClass = '';
        if ($widget->hasHeader()) {
            $dialogClass .= ' exf-dialog-with-header';
        }
        if ($widget->isFilledBySingleWidget() && $widget->getFillerWidget() instanceof Tabs) {
            $dialogClass .= ' exf-dialog-with-tabs';
        }
        
        if (! $this->getWidget()->getHideHelpButton()) {
            $window_tools = '<a href="javascript:' . $this->getFacade()->getElement($this->getWidget()->getHelpButton())->buildJsClickFunctionName() . '()" class="fa fa-question-circle-o"></a>';
        }
        
        $dialog_title = str_replace('"', '\"', $this->getCaption());
        
        $output = <<<HTML
        	<div class="easyui-dialog {$dialogClass}" id="{$this->getId()}" data-options="{$this->buildJsDataOptions()}" title="{$dialog_title}" style="width: {$this->getWidth()}; height: {$this->getHeight()}; max-width: 100%;">
        		{$children_html}
        	</div>
        	<div id="{$this->buttons_div_id}" class="exf-dialog-footer">
                {$this->buildHtmlToolbars()}
        	</div>
        	<div id="{$this->getId()}_window_tools">
        		{$window_tools}
        	</div><!-- /dialog -->
HTML;
        return $output;
    }

    /**
     * 
     * @return bool
     */
    public function isLayout() : bool
    {
        $widget = $this->getWidget();
        $noLayout = ($widget->getHeight()->isUndefined() || $widget->getHeight()->getValue() === 'auto') && $widget->hasSidebar() === false;
        return $noLayout === false;  
    }
    
    /**
     * 
     * @return bool
     */
    protected function hasButtonsVisible() : bool
    {
        foreach ($this->getWidget()->getButtons() as $btn) {
            if ($btn instanceof MenuButton) {
                if ($btn->isHidden() === false && $btn->hasButtons() === true) {
                    return true;
                }
            } elseif ($btn->isHidden() === false) {
                return true;
            }
        }
        return false;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJs()
     */
    public function buildJs()
    {
        $output = '';
        $widget = $this->getWidget();
        
        $output .= $this->buildJsForWidgets();
        if ($widget->hasHeader() === true) {
            $output .= $this->getFacade()->getElement($widget->getHeader())->buildJs();
        }
        $output .= $this->buildJsButtons();
        
        // Add the help button in the bottom toolbar
        if (! $widget->getHideHelpButton()) {
            $output .= $this->getFacade()->buildJs($widget->getHelpButton());
        }

        // Layout-Funktion hinzufuegen
        $output .= $this->buildJsLayouterFunction();
        
        return $output;
    }

    /**
     * Generates the contents of the data-options attribute (e.g.
     * iconCls, collapsible, etc.)
     *
     * @return string
     */
    public function buildJsDataOptions()
    {
        $this->addOnLoadScript("$('#" . $this->getId() . " .exf-input input').first().next().find('input').focus();");
        /* @var $widget \exface\Core\Widgets\Dialog */
        $widget = $this->getWidget();
        // TODO make the Dialog responsive as in http://www.jeasyui.com/demo/main/index.php?plugin=Dialog&theme=default&dir=ltr&pitem=
        $output = parent::buildJsDataOptions() 
            . ($widget->isMaximizable() ? ', maximizable: true, maximized: ' . ($widget->isMaximized() ? 'true' : 'false') : '') 
            . ", cache: false" 
            . ", closed: " . ($this->isLazyLoading() ? 'false' : 'true')
            . ($this->hasButtonsVisible() ? ", buttons: '#{$this->buttons_div_id}'" : '')
            . ", tools: '#{$this->getId()}_window_tools'" 
            . ", modal: true"
            . ", onBeforeClose: function() {" . str_replace('"', '\"', $this->buildJsDestroy()) . "}";
        return $output;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::getWidth()
     */
    public function getWidth()
    {
        $width = $this->getWidget()->getWidth();
        
        if ($width->isUndefined()) {
            $number_of_columns = $this->getNumberOfColumns();
            return ($number_of_columns * $this->getWidthRelativeUnit() + 35) . 'px';
        } 
        
        if ($width->isMax()) {
            return '100%';
        }
        
        if ($width->isRelative()) {
            return $width->getValue() * $this->getWidthRelativeUnit() + 35 . 'px';
        }
        
        return parent::getWidth();
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::getHeight()
     */
    public function getHeight()
    {
        $widget = $this->getWidget();
        if ($widget->getHeight()->isUndefined()) {
            if ($widget->getWidth()->isRelative() && $widget->getWidth()->getValue() === 1) {
                $this->getWidget()->setHeight('auto');
            } else {
                $this->getWidget()->setHeight('85%');
            }
        }
        return parent::getHeight();
    }

    /**
     *
     * {@inheritdoc}
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
     * Returns the default number of columns to layout this widget.
     *
     * @return integer
     */
    public function getNumberOfColumnsByDefault() : int
    {
        return $this->getFacade()->getConfig()->getOption("WIDGET.DIALOG.COLUMNS_BY_DEFAULT");
    }

    /**
     * Returns if the the number of columns of this widget depends on the number of columns
     * of the parent layout widget.
     *
     * @return boolean
     */
    public function inheritsNumberOfColumns() : bool
    {
        return false;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiWidgetGrid::getFitOption()
     */
    protected function getFitOption() : bool
    {
        return false;
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsOnCloseScript() : string
    {
        return $this->buildJsDestroy();
    }
}