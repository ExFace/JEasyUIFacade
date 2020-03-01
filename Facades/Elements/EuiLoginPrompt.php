<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\Tabs;
use exface\Core\DataTypes\BooleanDataType;
use exface\Core\Exceptions\Facades\FacadeRuntimeError;
use exface\Core\Interfaces\Widgets\iLayoutWidgets;

/**
 *
 * @author Andrej Kabachnik
 *        
 * @method Tabs getWidget()
 *        
 */
class EuiLoginPrompt extends EuiContainer
{

    private $fit_option = false;

    private $style_as_pills = false;

    protected function init()
    {
        parent::init();
        $this->setElementType('tabs');
    }

    public function buildHtml()
    {
        $widget = $this->getWidget();
        
        $width = $this->getWidthRelativeUnit();
        $style = "width: {$width}px";
        
        switch ($widget->getVisibility()) {
            case EXF_WIDGET_VISIBILITY_HIDDEN:
                $style .= ' visibility: hidden; height: 0px; padding: 0px;';
                break;
        }
        
        $output = <<<HTML
    <div style="width: 100%; text-align: center;">
        <div id="{$this->getId()}" style="{$style}" class="easyui-{$this->getElementType()} {$this->buildCssElementClass()}" data-options="{$this->buildJsDataOptions()}">
        	{$this->buildHtmlForWidgets()}
        </div>
    </div>

HTML;
        return $output;
    }
    
    public function buildHtmlForWidgets()
    {
        $output = '';
        foreach ($this->getWidget()->getWidgets() as $subw) {
            $title = $subw->getCaption();
            $subw->setHideCaption(true);
            
            $output .= '<div title="' . $title . '">' . $this->getFacade()->getElement($subw)->buildHtml() . "</div>\n";
        }
        return $output;
    }

    /**
     * 
     * @return string
     */
    public function buildJsDataOptions()
    {        
        return "tabPosition: 'top'";
    }

    protected function getFitOption()
    {
        return $this->fit_option;
    }
    
    public function buildCssElementClass()
    {
        return 'exf-loginprompt';
    }
}