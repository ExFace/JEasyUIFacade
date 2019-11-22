<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\InputNumber;
use exface\Core\DataTypes\NumberDataType;
use exface\Core\Factories\DataTypeFactory;

/**
 * @method InputNumber getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class EuiInputNumber extends EuiInput
{
    private $formatter = null;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::init()
     */
    protected function init()
    {
        parent::init();
        $this->setElementType('numberbox');
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJsDataOptions()
     */
    protected function buildJsDataOptions()
    {
        $widget = $this->getWidget();
        $output = parent::buildJsDataOptions();
        $output .= ($output ? ',' : '');
        
        $precision_max = $widget->getPrecisionMax();
        $precision_min = $widget->getPrecisionMin();
        if (is_null($precision_max) || $precision_min === $precision_max) {
            $formatter = 'return ' . $this->getDatatypeFormatter()->buildJsFormatter('value');
        }
        
        $output .= "precision: " . ($precision_max !== null ? $precision_max : 10)
                . ", decimalSeparator: '{$widget->getDecimalSeparator()}'"
                . ($formatter ?  ", formatter:function(value){" . $formatter . "}" : "")
				;
        return trim($output, ',');
    }
    
    /**
     * 
     * @return \exface\Core\Facades\AbstractAjaxFacade\Interfaces\JsDataTypeFormatterInterface
     */
    protected function getDatatypeFormatter() {
        if (is_null($this->formatter)) {
            $widget = $this->getWidget();
            $type = $widget->getValueDataType();
            if (! $type instanceof NumberDataType) {
                $type = DataTypeFactory::createFromPrototype($this->getWorkbench(), NumberDataType::class);
            }
            /* @var $formatter \exface\Core\Facades\AbstractAjaxFacade\Formatters\JsNumberFormatter */
            $this->formatter = $this->getFacade()->getDataTypeFormatter($type);
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
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildHtmlHeadTags()
     */
    public function buildHtmlHeadTags()
    {
        $formatter = $this->getDataTypeFormatter();
        return array_merge(parent::buildHtmlHeadTags(), $formatter->buildHtmlHeadIncludes($this->getFacade()), $formatter->buildHtmlBodyIncludes($this->getFacade()));
    }
}