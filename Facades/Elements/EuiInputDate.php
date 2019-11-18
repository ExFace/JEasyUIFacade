<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryInputDateTrait;
use exface\Core\Widgets\InputDate;
use exface\Core\DataTypes\DateDataType;
use exface\Core\Facades\AbstractAjaxFacade\Formatters\JsDateFormatter;

// Es waere wuenschenswert die Formatierung des Datums abhaengig vom Locale zu machen.
// Das Problem dabei ist folgendes: Wird im DateFormatter das Datum von DateJs ent-
// sprechend dem Locale formatiert, so muss der DateParser kompatibel sein. Es kommt
// sonst z.B. beim amerikanischen Format zu Problemen. Der 5.11.2015 wird als 11/5/2015
// formatiert, dann aber entsprechend den alexa RMS Formaten als 11.5.2015 geparst. Der
// Parser von DateJs kommt hingegen leider nicht mit allen alexa RMS Formaten zurecht.

// Eine Loesung waere fuer die verschiedenen Locales verschiedene eigene Parser zu
// schreiben, dann koennte man aber auch gleich verschiedene eigene Formatter
// hinzufuegen.
// In der jetzt umgesetzten Loesung wird das Anzeigeformat in den Uebersetzungsdateien
// festgelegt. Dabei ist darauf zu achten, dass es kompatibel zum Parser ist, das
// amerikanische Format MM/dd/yyyy ist deshalb nicht moeglich, da es vom Parser als
// dd/MM/yyyy interpretiert wird.

/**
 * Renders a jEasyUI datebox for an InputDate widget.
 * 
 * @method InputDate getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class EuiInputDate extends EuiInput
{
    
    use JqueryInputDateTrait;

    protected function init()
    {
        parent::init();
        $this->setElementType('datebox');
    }

    function buildHtml()
    {
        /* @var $widget \exface\Core\Widgets\Input */
        $widget = $this->getWidget();
        
        $value = $this->escapeString($this->getValueWithDefaults());
        $requiredScript = $widget->isRequired() ? 'required="true" ' : '';
        $disabledScript = $widget->isDisabled() ? 'disabled="disabled" ' : '';
        
        $output = <<<HTML

                <input style="height: 100%; width: 100%;"
                    id="{$this->getId()}"
                    name="{$widget->getAttributeAlias()}"
                    value="{$value}"
                    {$requiredScript}
                    {$disabledScript} />
HTML;
        
        return $this->buildHtmlLabelWrapper($output);
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
        date: {
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

    protected function buildJsDataOptions()
    {
        return <<<JS

        delay: 1,
        formatter: function (date) {
            // date ist ein date-Objekt und wird zu einem String geparst
            exfTools.date.format(date);
            console.log('formatter', date, exfTools.date.format(date));
            return (date instanceof Date ? exfTools.date.format(date) : '');
        },
        parser: function(string) {
            var date = exfTools.date.parse(string);
            console.log('parser', string, date);
            // Ausgabe des geparsten Wertes
            if (date) {
                $('#{$this->getId()}').data("_internalValue", {$this->getDataTypeFormatter()->buildJsDateStringifier('date')}).data("_isValid", true);
                return date;
            } else {
                $('#{$this->getId()}').data("_internalValue", "").data("_isValid", false);
                return null;
            }
        },
        onHidePanel: function() {
            // onHidePanel wird der Inhalt formatiert (beim Verlassen des Feldes), der
            // ausgefuehrte Code entspricht dem beim Druecken der Enter-Taste.
            var jqself = $(this);
            currentDate = jqself.{$this->getElementType()}("calendar").calendar("options").current;
            if (currentDate) {
                console.log('currentDate', currentDate);
                console.log('setValue', {$this->buildJsValueFormatter('currentDate')});
                jqself.{$this->getElementType()}("setValue", exfTools.date.format(currentDate));
            }
        },
        validType: "date['#{$this->getId()}']"
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
        $headers = parent::buildHtmlHeadTags();
        $headers = array_merge($headers, $formatter->buildHtmlHeadIncludes(), $formatter->buildHtmlBodyIncludes());
        return $headers;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsValueGetter()
     */
    public function buildJsValueGetter()
    {
        // Wird der Wert eines EuiInputDates in der Uxon-Beschreibung gesetzt, dann wird das
        // Widget vorbefuellt. Wird dieser vorbefuellte Wert manuell geloescht, dann wird kein
        // onChange getriggert (auch daran zu erkennen, dass das Panel nicht geoeffnet wird)
        // und der _internalValue bleibt auf dem vorherigen Wert. Das scheint ein Problem der
        // datebox zu sein.
        // Als workaround wird hier der aktuell angezeigte Wert abgerufen (nur getText()
        // liefert den korrekten (leeren) Wert, getValue() bzw. getValues() liefert auch den
        // bereits geloeschten Wert) und wenn dieser leer ist, wird auch der _internalValue
        // geleert.
        
        //return '$("#' . $this->getId() . '").data("_internalValue")';
        
        return <<<JS

        (function(){
            var jqself = $("#{$this->getId()}");
            if (jqself.data("{$this->getElementType()}") !== undefined && ! jqself.{$this->getElementType()}("getText")) {
                jqself.data("_internalValue", "");
            }
            return jqself.data("_internalValue");
        })()
JS;
    }
}