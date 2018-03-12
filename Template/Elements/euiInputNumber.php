<?php
namespace exface\JEasyUiTemplate\Template\Elements;

use exface\Core\Widgets\InputNumber;
use exface\Core\DataTypes\NumberDataType;

/**
 * @method InputNumber getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class euiInputNumber extends euiInput
{
    private $formatter = null;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUiTemplate\Template\Elements\euiInput::init()
     */
    protected function init()
    {
        parent::init();
        $this->setElementType('numberbox');
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUiTemplate\Template\Elements\euiInput::buildJsDataOptions()
     */
    protected function buildJsDataOptions()
    {
        $widget = $this->getWidget();
        $output = parent::buildJsDataOptions();
        $output = ($output ? ',' : '') . $output;
        
        $precision_max = $widget->getPrecisionMax();
        $precision_min = $widget->getPrecisionMin();
        if (is_null($precision_max) || $precision_min === $precision_max) {
            $formatter = 'return ' . $this->getDatatypeFormatter()->buildJsFormatter('value');
        }
        
        $output .= "precision: " . ($precision_max ? $precision_max : 10)
                . ", decimalSeparator: '{$widget->getDecimalSeparator()}'"
                . ($formatter ?  ", formatter:function(value){" . $formatter . "}" : "")
				;
        return trim($output, ',');
    }
    
    /**
     * 
     * @return \exface\Core\Templates\AbstractAjaxTemplate\Interfaces\JsDataTypeFormatterInterface
     */
    protected function getDatatypeFormatter() {
        if (is_null($this->formatter)) {
            $widget = $this->getWidget();
            $type = $widget->getValueDataType();
            if (! $type instanceof NumberDataType) {
                $type = new NumberDataType($this->getWorkbench());
            }
            /* @var $formatter \exface\Core\Templates\AbstractAjaxTemplate\Formatters\JsNumberFormatter */
            $this->formatter = $this->getTemplate()->getDataTypeFormatter($type);
            $this->formatter
                ->setDecimalSeparator($widget->getDecimalSeparator())
                ->setThousandsSeparator($widget->getThousandsSeparator());
        }
        
        return $this->formatter;
    }
    
    protected function buildJsValueFormatter($jsInput)
    {
        return $this->getDatatypeFormatter()->buildJsFormatter($jsInput);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildHtmlHeadTags()
     */
    public function buildHtmlHeadTags()
    {
        $formatter = $this->getDataTypeFormatter();
        return array_merge(parent::buildHtmlHeadTags(), $formatter->buildHtmlHeadIncludes(), $formatter->buildHtmlBodyIncludes());
    }
}