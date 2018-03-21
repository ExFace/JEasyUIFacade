<?php
namespace exface\JEasyUiTemplate\Templates\Elements;

use exface\Core\Widgets\DataList;

/**
 * 
 * @method DataList getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class euiDataList extends euiDataTable
{

    protected function init()
    {
        parent::init();
        $this->setElementType('datalist');
        
        $widget = $this->getWidget();
        if($widget->getConfiguratorWidget()->isEmpty() && is_null($widget->getToolbarMain()->setIncludeSearchActions(false))){
            $widget->setHideSearchButton(true);
        }
    }
    
    protected function getBaseHtmlElement()
    {
        return 'ul';
    }
    
}
?>