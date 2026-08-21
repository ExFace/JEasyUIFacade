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
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::getElementType()
     */
    public function getElementType() : ?string
    {
        return 'numberbox';
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
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsCallFunction($functionName, $parameters)
     */
    public function buildJsCallFunction(string $functionName = null, array $parameters = [], ?string $jsRequestData = null) : string
    {
        switch (true) {
            case $functionName === InputNumber::FUNCTION_ADD:
                return $this->buildJsCallFunctionAddSubtract($parameters);
        }
        return parent::buildJsCallFunction($functionName, $parameters, $jsRequestData);
    }
    
    /**
     * Adds (or subtracts) a number to the current value of the input.
     * 
     * @param array $parameters
     * @return string
     */
    protected function buildJsCallFunctionAddSubtract(array $parameters = []) : string
    {
        $formatter = $this->getDatatypeFormatter();
        return <<<JS
(function(nStep){
    var sVal = {$this->buildJsValueGetter()};
    var nVal = {$formatter->buildJsFormatParser('sVal')};
    if (nVal === null || nVal === undefined || isNaN(nVal)) {
        nVal = 0;
    }
    var nNew = nVal + nStep;
    {$this->buildJsValueSetter($formatter->buildJsFormatter('nNew'))};
})(parseFloat('{$parameters[0]}'));

JS;
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