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

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::getElementType()
     */
    public function getElementType()
    {
        return 'datebox';
    }
    
    function buildHtml()
    {
        /* @var $widget \exface\Core\Widgets\Input */
        $widget = $this->getWidget();
        
        $value = $this->escapeString($widget->getValueWithDefaults(), false);
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
        $output = <<<JS

$(function() {    
    $("#{$this->getId()}")
    .data("_internalValue", "{$this->getWidget()->getValueWithDefaults()}")
    .{$this->getElementType()}({
        {$this->buildJsDataOptions()}
    });

    {$this->buildJsEventScripts()}
        
});

JS;
        
        return $output;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildsJsAddValidationType()
     */
    protected function buildsJsAddValidationType() : string
    {
        // Validator-Regel fuer InputDates hinzufuegen. Jetzt fuer jedes Widget einmal.
        // Einmal wuerde eigentlich reichen, geht aber in facade.js nicht, weil die
        // message uebersetzt werden muss.
        return <<<JS
        $.extend($.fn.validatebox.defaults.rules, {
            date: {
                validator: function(value, param) {
                    return $(param[0]).data("_isValid");
                },
                message: "{$this->translate("MESSAGE.INVALID.INPUTDATE")}"
            }
        });
JS;
    }

    protected function buildJsDataOptions()
    {
        return <<<JS

        delay: 1,
        formatter: function (date) {
            // date ist ein date-Objekt und wird zu einem String geparst
            return (date instanceof Date ? {$this->getDateFormatter()->buildJsFormatDateObjectToString('date')} : '');
        },
        parser: function(string) {
            var date = {$this->getDateFormatter()->buildJsFormatParserToJsDate('string')};
            // Ausgabe des geparsten Wertes
            if (date) {
                $(this).data("_internalValue", {$this->getDateFormatter()->buildJsFormatDateObjectToInternal('date')}).data("_isValid", true);
                return date;
            } else {
                $(this).data("_internalValue", "").data("_isValid", false);
                return null;
            }
        },
        onHidePanel: function() {
            // onHidePanel wird der Inhalt formatiert (beim Verlassen des Feldes), der
            // ausgefuehrte Code entspricht dem beim Druecken der Enter-Taste.
            var jqself = $(this);
            currentDate = jqself.{$this->getElementType()}("calendar").calendar("options").current;
            if (currentDate) {
                jqself.{$this->getElementType()}("setValue", {$this->buildJsValueFormatter('currentDate')});
            }
            if (jqself.{$this->getElementType()}('isValid')) {
                jqself.trigger("change") 
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
        $formatter = $this->getDateFormatter();
        $headers = parent::buildHtmlHeadTags();
        $headers = array_merge($headers, $formatter->buildHtmlHeadIncludes($this->getFacade()), $formatter->buildHtmlBodyIncludes($this->getFacade()));
        return $headers;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsValueGetter()
     */
    public function buildJsValueGetter()
    {
        // Löschen des _internalValue
        // Wird der Wert eines EuiInputDates in der Uxon-Beschreibung gesetzt, dann wird das
        // Widget vorbefuellt. Wird dieser vorbefuellte Wert manuell geloescht, dann wird kein
        // onChange getriggert (auch daran zu erkennen, dass das Panel nicht geoeffnet wird)
        // und der _internalValue bleibt auf dem vorherigen Wert. Das scheint ein Problem der
        // datebox zu sein.
        // Als workaround wird hier der aktuell angezeigte Wert abgerufen (nur getText()
        // liefert den korrekten (leeren) Wert, getValue() bzw. getValues() liefert auch den
        // bereits geloeschten Wert) und wenn dieser leer ist, wird auch der _internalValue
        // geleert.
        
        // It seems to take a lot of time to initialize the datebox, so we need a fallback
        // for the time when the getter is requested, but the internal value is not yet
        // there:
        // - If the value is a link, use the value of the linked widget directly (don't wait
        // until the value is set in the datebox)
        // - If the value is static - return it as string
        // - otherwise return an empty string
        
        if ($link = $this->getWidget()->getValueWidgetLink()) {
            if ($linkedEl = $this->getFacade()->getElement($link->getTargetWidget())) {
                $initialValue = $linkedEl->buildJsValueGetter($link->getTargetColumnId());
            }
        } else {
            $initialValue = '"' . $this->escapeString($this->getWidget()->getValueWithDefaults(), false) . '"';
        }
        
        return <<<JS
(function(){
            var jqself = $("#{$this->getId()}");
            if (jqself.data("{$this->getElementType()}") === undefined) {
                return {$initialValue};
            } else if(! jqself.{$this->getElementType()}("getText")) {
                jqself.data("_internalValue", "");
            }
            return jqself.data("_internalValue");
        })()
JS;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJsValidator()
     */
    public function buildJsValidator()
    {
        // The regular validator (calling `.datebox('isValid')`) throws an exception if called
        // too early - e.g. when checking required filters in a pages root data widget. It seems
        // the combogrid is not yet fully initialized although .combogrid is not undefined already.
        // The solution is to catch the particular error and to fall back to the generic JS validation
        // in this case.
        $regularValidatorJs = parent::buildJsValidator();
        return <<<JS
function(){
                        try {
                            return {$regularValidatorJs}
                        } catch (e) {
                            if (e.message.startsWith("Cannot read properties of undefined")) {
                                return {$this->buildJsValidatorViaTrait()};
                            }
                            throw e;
                        }
                    }()
JS;
    }
}