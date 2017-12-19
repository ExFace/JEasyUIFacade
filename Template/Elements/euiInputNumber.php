<?php
namespace exface\JEasyUiTemplate\Template\Elements;

use exface\Core\Widgets\InputNumber;

/**
 * @method InputNumber getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class euiInputNumber extends euiInput
{

    protected function init()
    {
        parent::init();
        $this->setElementType('numberbox');
    }

    protected function buildJsDataOptions()
    {
        $widget = $this->getWidget();
        $output = parent::buildJsDataOptions();
        $output = ($output ? ',' : '') . $output;
        
        $precision_max = $widget->getPrecisionMax();
        $precision_min = $widget->getPrecisionMin();
        $locale = $this->getWorkbench()->context()->getScopeSession()->getSessionLocale();
        if (is_null($precision_max) || $precision_min === $precision_max) {
            $formatter = static::buildJsNumberFormatter('value', $widget->getPrecisionMin(), $widget->getPrecisionMax(), $widget->getDecimalSeparator(), $widget->getThousandsSeparator(), $locale);
        }
        
        $output .= "precision: " . ($precision_max ? $precision_max : 10)
                . ", decimalSeparator: '{$widget->getDecimalSeparator()}'"
                . ($formatter ?  ", formatter:function(value){" . $formatter . "}" : "")
				;
        return trim($output, ',');
    }
    
    public static function buildJsNumberFormatter($value_js_var, $precision_min, $precision_max, $decimal_separator, $thousands_separator, $locale = null)
    {
        $separator_regex = preg_quote($decimal_separator);
        $precision_max = is_null($precision_max) ? 'undefined' : $precision_max;
        $precision_min = is_null($precision_min) ? 'undefined' : $precision_min;
        $locale = is_null($locale) ? 'undefined' : "'" . str_replace('_', '-', $locale) . "'";
        $use_grouping = $thousands_separator ? 'true' : 'false';
        
        $js = <<<JS
            
            if ({$value_js_var} !== null && {$value_js_var} !== undefined && {$value_js_var} !== ''){
    			if (this.nodeType > 0) {
                     // TODO check if the element is an instantiated numberbox somehow!
    			     {$value_js_var} = $.fn.numberbox.defaults.formatter.call(this,{$value_js_var});
                }
                {$value_js_var} = {$value_js_var}.toString().replace(/{$separator_regex}/g, '.').replace(/ /g, '');
    			var number = parseFloat({$value_js_var});
                if (! isNaN(number)) {
                    var {$value_js_var} = number.toLocaleString(
                        {$locale}, // use a string like 'en-US' to override browser locale
                        {
                            minimumFractionDigits: {$precision_min}, 
                            maximumFractionDigits: {$precision_max},
                            useGrouping: {$use_grouping}
                        }
                    );
                }
                return {$value_js_var};
            }

JS;
        
        return $js;
    }
}