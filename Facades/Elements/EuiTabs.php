<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\Tabs;
use exface\Core\DataTypes\BooleanDataType;
use exface\Core\Exceptions\Facades\FacadeRuntimeError;

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
        $tabPosition = $this->getTabPosition();
        
        return "border:false, tabPosition: '$tabPosition'" . ($this->getFitOption() ? ", fit: true" : "") . ($this->getStyleAsPills() ? ", pill: true" : "") . ($tabPosition == 'left' || $tabPosition == 'right' ? ', plain: true' : '') . $this->buildJsDataOptionHeaderWidth();
    }
    
    /**
     * top, bottom, left, right
     * @return string
     */
    protected function getTabPosition() : string
    {
        $pos = strtolower($this->getWidget()->getNavPosition($this->getTabPositionDefault()));
        if (in_array($pos, ['top', 'bottom', 'left', 'right']) === false) {
            throw new FacadeRuntimeError('Invalid tab position "' . $pos . '" for eui-tabs!');
        }
        return $pos;
    }
    
    /**
     *
     * @return string
     */
    protected function getTabPositionDefault() : string
    {
        return 'top';
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsDataOptionHeaderWidth() : string
    {
        return ($this->getWidget()->getHideNavCaptions() ? ', headerWidth: 38' : '');
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