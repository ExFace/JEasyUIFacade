<?php
namespace exface\JEasyUiTemplate\Templates\Elements;

use exface\Core\Widgets\Panel;
use exface\Core\DataTypes\BooleanDataType;

/**
 * The Panel widget is mapped to a panel in jEasyUI
 *
 * @author Andrej Kabachnik
 *        
 * @method Panel getWidget()
 */
class euiPanel extends euiWidgetGrid
{
    public function buildJsDataOptions()
    {
        $widget = $this->getWidget();
        $output = parent::buildJsDataOptions();
        $output .= ', collapsible: ' . ($widget->isCollapsible() ? 'true' : 'false');
        $output .= $widget->getIcon() ? ', iconCls:\'' . $this->buildCssIconClass($widget->getIcon()) . '\'' : '';
        return ltrim($output, ", ");
    }
}
?>