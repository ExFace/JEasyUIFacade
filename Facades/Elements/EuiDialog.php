<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\Tabs;
use exface\Core\Interfaces\WidgetInterface;
use exface\Core\Widgets\MenuButton;

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
        $this->setElementType('dialog');
    }
    
    /**
     *
     * @return boolean
     */
    protected function isLazyLoading()
    {
        return $this->getWidget()->getLazyLoading(false);
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
        if (! $this->isLazyLoading()) {
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
            
            if ($widget->hasHeader() === true) {
                $headerElem = $this->getFacade()->getElement($widget->getHeader());
                $children_html = <<<HTML

    <div class="easyui-layout" data-options="fit:true">
        <div data-options="region:'north'" class="exf-dialog-header" style="height: {$headerElem->getHeight()}">
            {$headerElem->buildHtml()}
        </div>
        <div data-options="region:'center'">
            {$children_html}
        </div>
    </div>

HTML;
            }
        }
        
        if (! $this->getWidget()->getHideHelpButton()) {
            $window_tools = '<a href="javascript:' . $this->getFacade()->getElement($this->getWidget()->getHelpButton())->buildJsClickFunctionName() . '()" class="fa fa-question-circle-o"></a>';
        }
        
        $dialog_title = str_replace('"', '\"', $this->getCaption());
        
        $output = <<<HTML
	<div class="easyui-dialog" id="{$this->getId()}" data-options="{$this->buildJsDataOptions()}" title="{$dialog_title}" style="width: {$this->getWidth()}; height: {$this->getHeight()}; max-width: 100%;">
		{$children_html}
	</div>
	<div id="{$this->buttons_div_id}">
        {$this->buildHtmlToolbars()}
	</div>
	<div id="{$this->getId()}_window_tools">
		{$window_tools}
	</div>
HTML;
        return $output;
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
        if (! $this->isLazyLoading()) {
            $output .= $this->buildJsForWidgets();
            if ($this->getWidget()->hasHeader() === true) {
                $output .= $this->getFacade()->getElement($this->getWidget()->getHeader())->buildJs();
            }
        }
        $output .= $this->buildJsButtons();
        
        // Add the help button in the bottom toolbar
        if (! $this->getWidget()->getHideHelpButton()) {
            $output .= $this->getFacade()->buildJs($this->getWidget()->getHelpButton());
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
            . ", closed: false" 
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
        if ($this->getWidget()->getHeight()->isUndefined()) {
            if ($this->getWidget()->getColumnsInGrid() === 1) {
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
    
    protected function getFitOption()
    {
        return false;
    }
    
    protected function buildJsOnCloseScript() : string
    {
        return $this->buildJsDestroy();
    }
}
?>