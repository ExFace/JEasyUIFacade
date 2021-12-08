<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\Tabs;

/**
 * Renders a WidgetCarousel as eui-tabs with pills aligned in the center in the nav-strip.
 * 
 * @author Andrej Kabachnik
 *        
 * @method Tabs getWidget()
 *        
 */
class EuiWidgetCarousel extends EuiTabs
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiTabs::getElementType()
     */
    public function getElementType() : ?string
    {
        return 'tabs';
    }
    
    protected function init()
    {
        parent::init();
        $this->setStyleAsPills(true);
        
        foreach ($this->getWidget()->getTabs() as $nr => $tab) {
            if (($tab->getCaption() === null && $tab->getIcon() === null)
                || ($this->getWidget()->getHideNavCaptions() === true && $tab->getIcon() === null)) {
                $tab->setCaption($nr+1);
                $tab->setHideCaption(false);
            }
        }
    }
    
    protected function getTabPositionDefault() : string
    {
        return Tabs::NAV_POSITION_BOTTOM;
    }
    
    public function buildCssElementClass()
    {
        return 'exf-widget-carousel exf-element';
    }
    
    protected function getBorderOption() : bool
    {
        return true;
    }
}