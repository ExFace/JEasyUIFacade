<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\Tabs;
use exface\Core\DataTypes\BooleanDataType;

/**
 *
 * @author Andrej Kabachnik
 *        
 * @method Tabs getWidget()
 *        
 */
class EuiTabs extends EuiContainer
{

    private $fit_option = true;

    private $style_as_pills = false;

    protected function init()
    {
        parent::init();
        $this->setElementType('tabs');
    }

    public function buildHtml()
    {
        $widget = $this->getWidget();
        switch ($widget->getVisibility()) {
            case EXF_WIDGET_VISIBILITY_HIDDEN:
                $style = 'visibility: hidden; height: 0px; padding: 0px;';
                break;
            default:
                $style = '';
        }
        $output = <<<HTML
    <div id="{$this->getId()}" style="{$style}" class="easyui-{$this->getElementType()} {$this->buildCssElementClass()}" data-options="{$this->buildJsDataOptions()}">
    	{$this->buildHtmlForChildren()}
    </div>
HTML;
        return $output;
    }

    /**
     * 
     * @return string
     */
    public function buildJsDataOptions()
    {
        $widget = $this->getWidget();
        
        return "border:false, tabPosition: '" . $widget->getNavPosition($this->getDefaultNavPosition()) . "'" . ($this->getFitOption() ? ", fit: true" : "") . ($this->getStyleAsPills() ? ", pill: true" : "") . ($widget->getNavPosition() == Tabs::NAV_POSITION_LEFT || $widget->getNavPosition() == Tabs::NAV_POSITION_RIGHT ? ', plain: true' : '') . ($widget->getHideNavCaptions() ? ', headerWidth: 38' : '');
    }
    
    /**
     * 
     * @return string
     */
    protected function getDefaultNavPosition() : string
    {
        return Tabs::NAV_POSITION_TOP;
    }

    public function setFitOption($value)
    {
        $this->fit_option = BooleanDataType::cast($value);
        return $this;
    }

    protected function getFitOption()
    {
        return $this->fit_option;
    }

    public function getStyleAsPills()
    {
        return $this->style_as_pills;
    }

    public function setStyleAsPills($style_as_pills)
    {
        $this->style_as_pills = BooleanDataType::cast($style_as_pills);
        return $this;
    }

    /**
     * Returns the default number of columns to layout this widget.
     *
     * @return integer
     */
    public function getNumberOfColumnsByDefault() : int
    {
        return $this->getFacade()->getConfig()->getOption("WIDGET.TABS.COLUMNS_BY_DEFAULT");
    }

    public function addOnResizeScript($js)
    {
        foreach ($this->getWidget()->getTabs() as $tab) {
            $this->getFacade()->getElement($tab)->addOnResizeScript($js);
        }
        return $this;
    }
}
?>