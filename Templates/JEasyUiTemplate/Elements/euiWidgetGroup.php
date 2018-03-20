<?php
namespace exface\JEasyUiTemplate\Templates\JEasyUiTemplate\Elements;

class euiWidgetGroup extends euiPanel
{
    protected function init(){
        parent::init();
        $this->addElementCssClass('exf-widget-group');
    }
    
    public function buildJsDataOptions()
    {
        return parent::buildJsDataOptions() . ', border: false';
    }
}
?>