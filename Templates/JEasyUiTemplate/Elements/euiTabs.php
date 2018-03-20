<?php
namespace exface\JEasyUiTemplate\Templates\JEasyUiTemplate\Elements;

use exface\Core\Widgets\Tabs;
use exface\Core\DataTypes\BooleanDataType;

/**
 *
 * @author Andrej Kabachnik
 *        
 * @method Tabs getWidget()
 *        
 */
class euiTabs extends euiContainer
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
    <div id="{$this->getId()}" style="{$style}" class="easyui-{$this->getElementType()}" data-options="{$this->buildJsDataOptions()}">
    	{$this->buildHtmlForChildren()}
    </div>
HTML;
        return $output;
    }

    public function buildJsDataOptions()
    {
        $widget = $this->getWidget();
        
        return "border:false, tabPosition: '" . $widget->getTabPosition() . "'" . ($this->getFitOption() ? ", fit: true" : "") . ($this->getStyleAsPills() ? ", pill: true" : "") . ($widget->getTabPosition() == Tabs::TAB_POSITION_LEFT || $widget->getTabPosition() == Tabs::TAB_POSITION_RIGHT ? ', plain: true' : '') . ($widget->getHideTabsCaptions() ? ', headerWidth: 38' : '');
    }

    public function setFitOption($value)
    {
        $this->fit_option = BooleanDataType::cast($value);
        return $this;
    }

    public function getFitOption()
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
    public function getDefaultColumnNumber()
    {
        return $this->getTemplate()->getConfig()->getOption("WIDGET.TABS.COLUMNS_BY_DEFAULT");
    }

    public function addOnResizeScript($js)
    {
        foreach ($this->getWidget()->getTabs() as $tab) {
            $this->getTemplate()->getElement($tab)->addOnResizeScript($js);
        }
        return $this;
    }
}
?>