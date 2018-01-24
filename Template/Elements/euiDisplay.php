<?php
namespace exface\JEasyUiTemplate\Template\Elements;

use exface\Core\Interfaces\DataTypes\EnumDataTypeInterface;
use exface\Core\DataTypes\NumberDataType;
use exface\Core\DataTypes\DateDataType;
use exface\Core\DataTypes\TimestampDataType;
use exface\Core\DataTypes\TextStylesDataType;

/**
 * @method Display getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class euiDisplay extends euiValue
{
    protected function init()
    {
        parent::init();
        $this->setElementType($this->getCaption() ? 'span' : 'p');
        return;
    }
    
    public function generateHtml()
    {
        $widget = $this->getWidget();
        
        $style = 'text-align: ' . $this->buildCssTextAlignValue($widget->getAlign(), EXF_ALIGN_LEFT);
        $text = nl2br($this->getWidget()->getText());
        
        if ($widget->getAttribute()) {
            switch ($widget->getStyle()) {
                case TextStylesDataType::BOLD:
                    $style .= "font-weight: bold;";
                    break;
                case TextStylesDataType::ITALIC:
                    $style .= "font-style: italic;";
                    break;
                case TextStylesDataType::UNDERLINE:
                    $style .= "text-decoration: underline;";
                    break;
                case TextStylesDataType::UNDERLINE:
                    $style .= "text-decoration: line-through;";
                    break;
            }
        }
        
        $output = <<<HTML

        <{$this->getElementType()} id="{$this->getId()}" class="exf-text {$this->buildCssElementClass()}" style="{$style}">{$text}</{$this->getElementType()}>

HTML;
        return $this->buildHtmlLabelWrapper($output);
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUiTemplate\Template\Elements\euiValue::generateJs()
     */
    public function generateJs()
    {
        if ($this->hasFormatter()) {
            return $this->buildJsValueSetter($this->buildJsValueGetter()) . ';';
        }
        
        return '';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJsValueSetter()
     */
    public function buildJsValueSetter($value, $disable_formatting = false)
    {
        if (! $disable_formatting && $this->hasFormatter()) {
            $value = $this->buildJsValueFormatter($value);
        }
        
        return "$('#{$this->getId()}').html({$value})";
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJsValueGetter()
     */
    public function buildJsValueGetter()
    {
        return "$('#{$this->getId()}').html()";
    }
    
    /**
     * Returns inline JS code, that formats the given value.
     * 
     * The result may be a function call or an immediately invoked anonymous function (IFEE).
     * NOTE: In any case, there is no ending semicolon!
     * 
     * @param string $js_value
     * @return string
     */
    protected function buildJsValueFormatter($value_js) 
    {
        $type = $this->getWidget()->getValueDataType();
        $js_var_value = 'val';
        switch (true) {
            case $type instanceof EnumDataTypeInterface :
                $js_value_labels = json_encode($type->getLabels());
                $formatter = <<<JS
                
        var labels = {$js_value_labels};
        return labels[{$js_var_value}] ? labels[{$js_var_value}] : {$js_var_value};
        
JS;
                break;
            case $type instanceof NumberDataType :
                $translator = $this->getWorkbench()->getCoreApp()->getTranslator();
                $decimal_separator = $translator->translate('LOCALIZATION.NUMBER.DECIMAL_SEPARATOR');
                $thousands_separator = $type->getGroupDigits() ? $translator->translate('LOCALIZATION.NUMBER.THOUSANDS_SEPARATOR') : '';
                $locale = $this->getWorkbench()->context()->getScopeSession()->getSessionLocale();
                $formatter = euiInputNumber::buildJsNumberFormatter($js_var_value, $type->getPrecisionMin(), $type->getPrecisionMax(), $decimal_separator, $thousands_separator, $locale);
                break;
            case $type instanceof DateDataType:
            case $type instanceof TimestampDataType:
                $format = $type instanceof TimestampDataType ? $this->translate("DATETIME.FORMAT.SCREEN") : $this->translate("DATE.FORMAT.SCREEN");
                $formatter = <<<JS
        if (! {$js_var_value}) {
            return {$js_var_value};
        }
        return Date.parse({$js_var_value}).toString("{$format}");
        
JS;
                break;
            default:
                return $value_js;
        }
        
        // Formatter option
        return <<<JS
function(){
    var {$js_var_value} = {$value_js}; 
    try {
        {$formatter}
    } catch (e) {
        return {$js_var_value};
    } 
}()

JS;
    }
        
    protected function hasFormatter()
    {
        return $this->buildJsValueFormatter('') !== '';
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildCssHeightDefaultValue()
     */
    protected function buildCssHeightDefaultValue()
    {
        return 'auto';
    }
}
?>