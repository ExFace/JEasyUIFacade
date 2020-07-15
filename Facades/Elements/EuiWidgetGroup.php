<?php
namespace exface\JEasyUIFacade\Facades\Elements;

class EuiWidgetGroup extends EuiPanel
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiWidgetGrid::init()
     */
    protected function init(){
        parent::init();
        $this->addElementCssClass('exf-panel-flat exf-widget-group');
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiPanel::buildJsDataOptions()
     */
    public function buildJsDataOptions()
    {
        return parent::buildJsDataOptions() . ', border: false';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiWidgetGrid::getFitOption()
     */
    public function getFitOption() : bool
    {
        return true;
    }
}