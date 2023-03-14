<?php
namespace exface\JEasyUIFacade\Facades\Elements;

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