<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\Panel;
use exface\Core\DataTypes\BooleanDataType;
use exface\Core\Widgets\Tiles;
use exface\Core\Widgets\NavTiles;

/**
 * The Panel widget is mapped to a panel in jEasyUI
 *
 * @author Andrej Kabachnik
 *        
 * @method Panel getWidget()
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
        if ($widget->isFilledBySingleWidget() || ($widget->countWidgetsVisible() === 1 && ($widget->getWidgetFirst() instanceof Tiles || $widget->getWidgetFirst() instanceof NavTiles))) {
            $classes .= ' panel-filled';
        }
        return $classes;
    }
}