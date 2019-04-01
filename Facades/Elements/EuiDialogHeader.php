<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\DialogHeader;
use exface\Core\Factories\WidgetFactory;
use exface\Core\CommonLogic\UxonObject;
use exface\Core\Widgets\WidgetGroup;

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
        $caption = $widget->getCaption();
        $widget->setHideCaption(true);
        
        $heading = WidgetFactory::createFromUxon($widget->getPage(), new UxonObject([
            'widget_type' => 'TextHeading',
            'text' => $widget->getMetaObject()->getName() . ' ' . $caption
        ]), $widget);
        
        if ($widget->getWidgetFirst() instanceof WidgetGroup) {
            $widget->getWidgetFirst()->addWidget($heading, 0);
        } else {
            $widget->addWidget($heading, 0);
        }
    }
    
    public function buildCssElementClass()
    {
        return 'exf-dialog-header';
    }
}