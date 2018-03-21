<?php
namespace exface\JEasyUiTemplate\Templates\Elements;

use exface\Core\Widgets\ColorIndicator;
use exface\Core\Widgets\DataColumn;
use exface\Core\CommonLogic\Constants\Colors;
use exface\Core\CommonLogic\Model\Condition;
use exface\Core\DataTypes\ComparatorDataType;

/**
 * Creates colored display elements from ColorIndicator widgets.
 * 
 * If the widget is used as cell widget in a jEasyUI DataGrid, the styler property
 * of the DataGrid column will be used instead of the regular logic for other
 * elements.
 * 
 * @method ColorIndicator getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class euiColorIndicator extends euiDisplay
{
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Interfaces\JsValueDecoratingInterface::buildJsValueDecorator()
     */
    public function buildJsValueDecorator($value_js) {
        $js = '';
        $widget = $this->getWidget();
        $parentWidget = $widget->getParent();
        
        if ($parentWidget instanceof DataColumn) {
            $dataElement = $this->getTemplate()->getElement($widget->getParent()->getDataWidget());
            if ($dataElement instanceof euiDataTable) {
                $parentWidget->setCellStylerScript($this->buildJsDataGridStyler());
                $js = parent::buildJsValueFormatter($value_js);
            }
        } else {
            $ifs = '';
            foreach ($widget->getColorConditions() as $condition) {
                $target = is_string($condition->getValue()) ? "'" . $condition->getValue() . "'" : $condition->getValue();
                $comp = $condition->getComparator();
                switch ($comp) {
                    // TODO add comparators like IN
                    default:
                        if ($comp === ComparatorDataType::IS) {
                            $comp = '==';
                        }
                        $ifs .= "if ({$value_js} {$comp} {$target}) css = '" . $this->buildCssColorProperties($condition) . "';";
                }
            }
            $js = <<<JS

function(){
    var css = '';
    {$ifs}
    if (css) {
        $('#{$this->getId()}').css(css);
    }
    return {$this->buildJsValueFormatter($value_js)};
}()

JS;
        }
        
        return $js;
    }
    
    /**
     * Returns a javascript snippet to be inserted into the styler property of a jEasyUI
     * DataGrid to set the right color for the cell.
     * 
     * @return string
     */
    protected function buildJsDataGridStyler()
    {
        $script = '';
        $widget = $this->getWidget();
        foreach ($widget->getColorConditions() as $condition) {
            if ($compCol = $widget->getParent()->getDataWidget()->getColumnByAttributeAlias($condition->getValue())) {
                $compare_to_js = 'row["' . $compCol->getDataColumnName() . '"]';
                $compare_to_js .= ' && ' . $compare_to_js . ' !== "" && ' . $compare_to_js . ' !== null && ' . $compare_to_js . ' !== undefined';
            } else {
                $compare_to_js = is_string($condition->getValue()) ? "'" . $condition->getValue() . "'" : $condition->getValue();
            }
            $css = $this->buildCssColorProperties($condition);
            $comp = $condition->getComparator();
            switch ($comp) {
                // TODO add comparators like IN
                default:
                    if ($comp === ComparatorDataType::IS) {
                        $comp = '==';
                    }
                    $script .= 'if (value ' . $condition->getComparator() . $compare_to_js . ') return "' . $css . '";';
            }
        }
        return $script;
    }
    
    /**
     * Generates CSS attributes for the given condition: e.g. background-color: red; color: white;
     * 
     * @param Condition $condition
     * @return string
     */
    protected function buildCssColorProperties(Condition $condition)
    {
        $widget = $this->getWidget();
        $color = $widget->getColorOfCondition($condition);
        if ($widget->getFill()) {
            $css = "background-color:" . $color . ";";
            if (Colors::isDark($color)) {
                $css .= 'color:white;';
            }
        } else {
            $css = "color:" . $color . ";";
        }
        
        return $css;
    }
    
}
?>