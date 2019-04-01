<?php
namespace exface\JEasyUIFacade\Facades\Elements;

class EuiWidgetGroup extends EuiPanel
{
    protected function init(){
        parent::init();
        $this->addElementCssClass('exf-panel-flat exf-widget-group');
    }
    
    public function buildJsDataOptions()
    {
        return parent::buildJsDataOptions() . ', border: false';
    }
}
?>