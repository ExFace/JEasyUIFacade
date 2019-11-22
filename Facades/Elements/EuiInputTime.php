<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryInputDateTrait;
use exface\Core\Widgets\InputTime;
use exface\Core\DataTypes\NumberDataType;
use exface\Core\Factories\DataTypeFactory;

/**
 * @method InputTime getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class EuiInputTime extends EuiInput
{
    use JqueryInputDateTrait;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::init()
     */
    protected function init()
    {
        parent::init();
        $this->setElementType('timespinner');
    }
    
    function buildJs()
    {
        // Validator-Regel fuer InputDates hinzufuegen. Jetzt fuer jedes Widget einmal.
        // Einmal wuerde eigentlich reichen, geht aber in facade.js nicht, weil die
        // message uebersetzt werden muss.
        $output = <<<JS
        
$(function() {

    // Validator-Regel fuer InputDates hinzufuegen.
    $.extend($.fn.validatebox.defaults.rules, {
        time: {
            validator: function(value, param) {
                return $(param[0]).data("_isValid");
            },
            message: "{$this->translate("MESSAGE.INVALID.INPUTDATE")}"
        }
    });
    
    $("#{$this->getId()}")
    .data("_internalValue", "{$this->getValueWithDefaults()}")
    .{$this->getElementType()}({
        {$this->buildJsDataOptions()}
    });
    
});

JS;
        
        return $output;
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
        $output = ($output ? ',' : '') . $output;
             
        $output .= "showSeconds: " . ($widget->getShowSeconds() ? 'true' : 'false')
                . ", increment: " . ($widget->getStepMinutes() < 60 ? $widget->getStepMinutes() : $widget->getStepMinutes() / 60)
                . ", highlight: " . ($widget->getStepMinutes() < 60 ? 1 : 0)
                ;
        $output .= <<<JS
                
        , delay: 1
        , parser: function(string) {
            var date = {$this->getDateFormatter()->buildJsFormatParserToJsDate('string')};
            // Ausgabe des geparsten Wertes
            if (date) {
                $('#{$this->getId()}').data("_internalValue", {$this->getDateFormatter()->buildJsFormatDateObjectToInternal('date')}).data("_isValid", true);
                return date;
            } else {
                $('#{$this->getId()}').data("_internalValue", "").data("_isValid", false);
                return null;
            }
        }
        , validType: "time['#{$this->getId()}']"
JS;
        return trim($output, ',');
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildHtmlHeadTags()
     */
    public function buildHtmlHeadTags()
    {
        $formatter = $this->getDateFormatter();
        return array_merge(parent::buildHtmlHeadTags(), $formatter->buildHtmlHeadIncludes(), $formatter->buildHtmlBodyIncludes());
    }
}