<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\DialogHeader;
use exface\Core\Factories\WidgetFactory;
use exface\Core\CommonLogic\UxonObject;
use exface\Core\Widgets\WidgetGroup;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryMasonryGridTrait;

/**
 *
 * @method DialogHeader getWidget()
 * @author Andrej Kabachnik
 *        
 */
class EuiDialogHeader extends EuiWidgetGrid
{
    protected function init()
    {
        parent::init();
        $widget = $this->getWidget();
        $caption = $this->getCaption();
        $widget->setHideCaption(true);
        
        if ($caption) {
            $heading = WidgetFactory::createFromUxon($widget->getPage(), new UxonObject([
                'widget_type' => 'TextHeading',
                'text' => $caption,
                'width' => 'max'
            ]), $widget);
            
            if ($widget->getWidgetFirst() instanceof WidgetGroup) {
                $widget->getWidgetFirst()->addWidget($heading, 0);
            } else {
                $widget->addWidget($heading, 0);
            }
        }
    }
    
    public function buildCssElementClass()
    {
        return 'exf-dialog-header';
    }
    
    /**
     * After the regular grid layouter finishes, the header will adjust the size of its parent
     * jEasyUI layout element 
     * 
     * @see JqueryMasonryGridTrait::buildJsLayouter()
     */
    public function buildJsLayouter() : string
    {
        return parent::buildJsLayouter() . <<<JS
(function(){
                var jqPanel = $('#{$this->getId()}').parents('.easyui-layout').first().layout('panel','north');
                if (! jqPanel) return;
                var jqGrid = jqPanel.find('.grid');
                if (! jqGrid) return;
                var iHeight = jqGrid.outerHeight() + 20;
                if (jqPanel.height() > iHeight) {
                    jqPanel.height(iHeight);
                }
            })();
JS;
    }
}