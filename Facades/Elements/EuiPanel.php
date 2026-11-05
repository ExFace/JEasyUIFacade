<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\CommonLogic\Constants\Icons;

/**
 * The Panel widget is mapped to a panel in jEasyUI
 *
 * @author Andrej Kabachnik
 *        
 * @method \exface\Core\Widgets\Panel getWidget()
 */
class EuiPanel extends EuiWidgetGrid
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiWidgetGrid::buildJsDataOptions()
     */
    public function buildJsDataOptions()
    {
        $widget = $this->getWidget();
        $output = parent::buildJsDataOptions();
        $output .= ', collapsible: ' . ($widget->isCollapsible() ? 'true' : 'false');
        $output .= $widget->getIcon() ? ', iconCls:\'' . $this->buildCssIconClass($widget->getIcon()) . '\'' : '';
        if (Icons::isIconSetSVG($widget->getIconSet()) && null !== $icon = $widget->getIcon()) {
            // EasyUI creates the icon container, but only knows how to populate font icons.
            $iconJs = $this->escapeString($this->escapeString($icon, true, false), false, true);
            $output .= ", onBeforeOpen: function(){
                $(this).panel('header').find('.panel-icon').html({$iconJs});
            }";
        }
        return ltrim($output, ", ");
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiWidgetGrid::buildCssElementClass()
     */
    public function buildCssElementClass()
    {
        $classes = parent::buildCssElementClass();
        $widget = $this->getWidget();
        if ($widget->isFilledBySingleWidget()) {
            $classes .= ' panel-filled';
        }
        return $classes;
    }
}