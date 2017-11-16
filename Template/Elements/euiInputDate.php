<?php
namespace exface\JEasyUiTemplate\Template\Elements;

use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryInputDateTrait;

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
class euiInputDate extends euiInput
{
    
    use JqueryInputDateTrait;

    protected function init()
    {
        parent::init();
        $this->setElementType('datebox');
    }

    function generateHtml()
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

    function generateJs()
    {
        // Validator-Regel fuer InputDates hinzufuegen. Jetzt fuer jedes Widget einmal.
        // Einmal wuerde eigentlich reichen, geht aber in template.js nicht, weil die
        // message uebersetzt werden muss.
        $output = <<<JS

    // Validator-Regel fuer InputDates hinzufuegen.
    $.extend($.fn.validatebox.defaults.rules, {
        date: {
            validator: function(value, param) {
                return $(param[0]).data("_isValid");
            },
            message: "{$this->translate("MESSAGE.INVALID.INPUTDATE")}"
        }
    });
    
    $("#{$this->getId()}").{$this->getElementType()}({
        {$this->buildJsDataOptions()}
    });
    
    {$this->buildJsDateParser()}
    {$this->buildJsDateFormatter()}
JS;
        
        return $output;
    }

    protected function buildJsDataOptions()
    {
        return <<<JS

        delay: 0,
        formatter: {$this->buildJsFunctionPrefix()}dateFormatter,
        parser: {$this->buildJsFunctionPrefix()}dateParser,
        onHidePanel: function() {
            // onHidePanel wird der Inhalt formatiert (beim Verlassen des Feldes), der
            // ausgefuehrte Code entspricht dem beim Druecken der Enter-Taste.
            {$this->getId()}_jquery = $("#{$this->getId()}");
            currentDate = {$this->getId()}_jquery.{$this->getElementType()}("calendar").calendar("options").current;
            if (currentDate) {
                {$this->getId()}_jquery.{$this->getElementType()}("setValue", {$this->buildJsFunctionPrefix()}dateFormatter(currentDate));
            }
        },
        validType: "date['#{$this->getId()}']"
JS;
    }

    public function generateHeaders()
    {
        $headers = parent::generateHeaders();
        $headers[] = '<script type="text/javascript" src="exface/vendor/npm-asset/datejs/build/production/' . $this->buildDateJsLocaleFilename() . '"></script>';
        return $headers;
    }

    public function buildJsValueGetter()
    {
        // Wird der Wert eines euiInputDates in der Uxon-Beschreibung gesetzt, dann wird das
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
            var {$this->getId()}_jquery = $("#{$this->getId()}");
            if (! {$this->getId()}_jquery.{$this->getElementType()}("getText")) {
                {$this->getId()}_jquery.data("_internalValue", "");
            }
            return {$this->getId()}_jquery.data("_internalValue");
        })()
JS;
    }

    protected function buildJsDateFormatter()
    {
        // Das Format in dateFormatScreen muss mit dem DateParser kompatibel sein. Das
        // amerikanische Format MM/dd/yyyy wird vom Parser als dd/MM/yyyy interpretiert
        // und kann deshalb nicht verwendet werden. Loesung waere den Parser anzupassen.
        
        // Auch moeglich: Verwendung des DateJs-Formatters:
        // "d" entspricht CultureInfo shortDate Format Pattern, hierfuer muss das
        // entsprechende locale DateJs eingebunden werden und ein kompatibler Parser ver-
        // wendet werden
        // return date.toString("d");
        $output = <<<JS

    function {$this->buildJsFunctionPrefix()}dateFormatter(date) {
        // date ist ein date-Objekt und wird zu einem String geparst
        return date.toString("{$this->buildJsDateFormatScreen()}");
    }
JS;
        
        return $output;
    }
}