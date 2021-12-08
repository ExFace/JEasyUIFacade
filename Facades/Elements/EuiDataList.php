<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\DataList;

/**
 * 
 * @method DataList getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class EuiDataList extends EuiDataTable
{

    protected function init()
    {
        parent::init();
        
        $widget = $this->getWidget();
        if($widget->getConfiguratorWidget()->isEmpty() && is_null($widget->getToolbarMain()->setIncludeSearchActions(false))){
            $widget->setHideSearchButton(true);
        }
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiDataTable::getElementType()
     */
    public function getElementType() : ?string
    {
        return 'datalist';
    }
    
    protected function getBaseHtmlElement()
    {
        return 'ul';
    }   
}