<?php
namespace exface\JEasyUiTemplate\Templates\Elements;

use exface\Core\Widgets\InputComboTable;
use exface\Core\Exceptions\Widgets\WidgetConfigurationError;
use exface\Core\Exceptions\InvalidArgumentException;
use exface\Core\DataTypes\UrlDataType;

/**
 *
 * @method InputComboTable getWidget()
 *        
 * @author Andrej Kabachnik
 *        
 */
class euiInputComboTable extends euiInput
{

    /**
     * Folgende privaten Variablen sind im data-Objekt des Elements gespeichert und wichtig
     * fuer die Funktion desselben:
     * _valueSetterUpdate ist gesetzt wenn der Wert des Objekts durch den Value-Setter
     * gesetzt wurde
     * _filterSetterUpdate ist gesetzt wenn sich eine Filter-Referenz geaendert hat
     * _clearFilterSetterUpdate ist gesetzt wenn das Objekt durch eine Filter-Referenz geleert
     * werden soll
     * _firstLoad ist nur beim ersten Laden gesetzt
     * _clear ist gesetzt wenn der Wert des Objekts geloescht wurde
     * _otherSuppressFilterSetterUpdate ist gesetzt wenn die durch eine Filter-Referenz abhaengigen
     * Objekte nicht aktualisiert werden sollen
     * _otherSupressLazyLoadingGroupUpdate ist gesetzt wenn die Objekte der gleichen LazyLoadingGroup
     * nicht aktualisiert werden sollen
     * _otherClearFilterSetterUpdate ist gesetzt wenn die durch eine Filter-Referenz abhaengigen
     * Objekte geleert werden sollen
     * _otherSuppressAllUpdates ist gesetzt wenn alle abhaengigen Objekte (durch Filter- oder
     * Value-Referenz) nicht aktualisiert werden sollen
     * _suppressReloadOnSelect ist gesetzt wenn nach dem selektieren eines Eintrags nicht
     * neu geladen werden soll (bei autoselectsinglesuggestion)
     * _currentText der seit der letzten gueltigen Auswahl eingegebene Text
     * _lastValidValue der letzte gueltige Wert des Objekts
     * _lastFilterSet die beim letzten Laden gesetzten Filter
     * _resultSetChanged ist gesetzt wenn die geladenen Daten veraendert wurden
     */
    private $js_debug_level = 0;

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiInput::init()
     */
    protected function init()
    {
        parent::init();
        $this->setElementType('combogrid');
        $this->setJsDebugLevel($this->getTemplate()->getConfig()->getOption("JAVASCRIPT_DEBUG_LEVEL"));
        
        // Register onChange-Handler for Filters with Live-Reference-Values
        $widget = $this->getWidget();
        if ($widget->getTable()->hasFilters()) {
            foreach ($widget->getTable()->getFilters() as $fltr) {
                if ($link = $fltr->getValueWidgetLink()) {
                    $linked_element = $this->getTemplate()->getElement($link->getTargetWidget());
                    
                    $widget_lazy_loading_group_id = $widget->getLazyLoadingGroupId();
                    $linked_element_lazy_loading_group_id = method_exists($linked_element->getWidget(), 'getLazyLoadingGroupId') ? $linked_element->getWidget()->getLazyLoadingGroupId() : '';
                    // Gehoert das Widget einer Lazyloadinggruppe an, so darf es keine Filterreferenzen
                    // zu Widgets außerhalb dieser Gruppe haben.
                    if ($widget_lazy_loading_group_id && ($linked_element_lazy_loading_group_id != $widget_lazy_loading_group_id)) {
                        throw new WidgetConfigurationError($widget, 'Widget "' . $widget->getId() . '" in lazy-loading-group "' . $widget_lazy_loading_group_id . '" has a filter-reference to widget "' . $linked_element->getWidget()->getId() . '" in lazy-loading-group "' . $linked_element_lazy_loading_group_id . '". Filter-references to widgets outside the own lazy-loading-group are not allowed.', '6V6C2HY');
                    }
                    
                    $on_change_script = <<<JS

                        if (typeof suppressFilterSetterUpdate == "undefined" || !suppressFilterSetterUpdate) {
                            if (typeof clearFilterSetterUpdate == "undefined" || !clearFilterSetterUpdate) {
                                $("#{$this->getId()}").data("_filterSetterUpdate", true);
                            } else {
                                $("#{$this->getId()}").data("_clearFilterSetterUpdate", true);
                            }
                            $("#{$this->getId()}").combogrid("grid").datagrid("reload");
                        }
JS;
                    
                    if ($widget_lazy_loading_group_id) {
                        $on_change_script = <<<JS

                    if (typeof suppressLazyLoadingGroupUpdate == "undefined" || !suppressLazyLoadingGroupUpdate) {
                        {$on_change_script}
                    }
JS;
                    }
                    
                    $linked_element->addOnChangeScript($on_change_script);
                }
            }
        }
        
        // Register an onChange-Script on the element linked by a disable condition.
        $this->registerDisableConditionAtLinkedElement();
        
        // Make sure, the table in the combo has a smaller default page size than regular (big) tables
        // This makes combotables faster with large data sets.
        if (is_null($this->getWidget()->getTable()->getPaginatePageSize())) {
            $this->getWidget()->getTable()->setPaginatePageSize($this->getTemplate()->getConfig()->getOption('WIDGET.INPUTCOMBOTABLE.PAGE_SIZE'));
        }
    }

    /**
     *
     * @throws WidgetConfigurationError
     * @return \exface\JEasyUiTemplate\Templates\Elements\euiInputComboTable
     */
    protected function registerLiveReferenceAtLinkedElement()
    {
        $widget = $this->getWidget();
        
        if ($linked_element = $this->getLinkedTemplateElement()) {
            // Gehoert das Widget einer Lazyloadinggruppe an, so darf es keine Value-
            // Referenzen haben.
            $widget_lazy_loading_group_id = $widget->getLazyLoadingGroupId();
            if ($widget_lazy_loading_group_id) {
                throw new WidgetConfigurationError($widget, 'Widget "' . $widget->getId() . '" in lazy-loading-group "' . $widget_lazy_loading_group_id . '" has a value-reference to widget "' . $linked_element->getWidget()->getId() . '". Value-references to other widgets are not allowed.', '6V6C3AP');
            }
            
            $linked_element->addOnChangeScript($this->buildJsLiveReference());
        }
        return $this;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiInput::buildHtml()
     */
    function buildHtml()
    {
        /* @var $widget \exface\Core\Widgets\InputComboTable */
        $widget = $this->getWidget();
        
        $value = $this->getValueWithDefaults();
        $nameScript = $widget->getAttributeAlias() . ($widget->getMultiSelect() ? '[]' : '');
        $requiredScript = $widget->isRequired() ? 'required="true" ' : '';
        $disabledScript = $widget->isDisabled() ? 'disabled="disabled" ' : '';
        
        $output = <<<HTML

                <input style="height:100%;width:100%;"
                    id="{$this->getId()}" 
                    name="{$nameScript}" 
                    value="{$value}"
                    {$requiredScript}
                    {$disabledScript} />
HTML;
        
        return $this->buildHtmlLabelWrapper($output);
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiInput::buildJs()
     */
    function buildJs()
    {
        $debug_function = ($this->getJsDebugLevel() > 0) ? $this->buildJsDebugDataToStringFunction() : '';
        
        $output = <<<JS

            // Globale Variablen initialisieren.
            {$this->buildJsInitGlobalsFunction()}
            {$this->buildJsFunctionPrefix()}initGlobals();
            // Debug-Funktionen hinzufuegen.
            {$debug_function}
			
            {$this->getId()}_jquery.combogrid({
                {$this->buildJsInitOptions()}
            });
            
JS;
        
        // Es werden JavaScript Value-Getter-/Setter- und OnChange-Funktionen fuer die InputComboTable erzeugt,
        // um duplizierten Code zu vermeiden.
        $output .= <<<JS

            {$this->buildJsValueGetterFunction()}
            {$this->buildJsValueSetterFunction()}
            {$this->buildJsOnChangeFunction()}
            {$this->buildJsClearFunction()}
JS;
        
        // Es werden Dummy-Methoden fuer die Filter der DataTable hinter dieser InputComboTable generiert. Diese
        // Funktionen werden nicht benoetigt, werden aber trotzdem vom verlinkten Element aufgerufen, da
        // dieses nicht entscheiden kann, ob das Filter-Input-Widget existiert oder nicht. Fuer diese Filter
        // existiert kein Input-Widget, daher existiert fuer sie weder HTML- noch JavaScript-Code und es
        // kommt sonst bei einem Aufruf der Funktion zu einem Fehler.
        if ($this->getWidget()->getTable()->hasFilters()) {
            foreach ($this->getWidget()->getTable()->getFilters() as $fltr) {
                $output .= <<<JS

            function {$this->getTemplate()->getElement($fltr->getInputWidget())->buildJsFunctionPrefix()}valueSetter(value){}
JS;
            }
        }
        
        // Initialize the disabled state of the widget if a disabled condition is set.
        $output .= $this->buildJsDisableConditionInitializer();
        
        // Add a clear icon to each combo grid - a small cross to the right, that resets the value
        // TODO The addClearBtn extension seems to break the setText method, so that it also sets the value. Perhaps we can find a better way some time
        // $output .= "$('#" . $this->getId() . "').combogrid('addClearBtn', 'icon-clear');";
        
        return $output;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiInput::buildJsInitOptions()
     */
    function buildJsInitOptions()
    {
        /* @var $widget \exface\Core\Widgets\InputComboTable */
        $widget = $this->getWidget();
        /* @var $table \exface\JEasyUiTemplate\Templates\Elements\DataTable */
        $table = $this->getTemplate()->getElement($widget->getTable());
        
        // Add explicitly specified values to every return data
        foreach ($widget->getSelectableOptions() as $key => $value) {
            if ($key === '' || $key === EXF_LOGICAL_NULL || is_null($key)){
                continue;
            }
            $table->addLoadFilterScript('data.rows.unshift({' . $widget->getTable()->getUidColumn()->getDataColumnName() . ': "' . $key . '", ' . $widget->getTextColumn()->getDataColumnName() . ': "' . $value . '"});');
        }
        
        // Init the combogrid itself
        $inheritedOptions = '';
        if ($this->isLazyLoading() || (! $this->isLazyLoading() && $widget->isDisabled())) {
            $inheritedOptions = $table->buildJsDataSource();
        }
        $table->setOnBeforeLoad($this->buildJsOnBeforeload());
        $table->addOnLoadSuccess($this->buildJsOnLoadSucess());
        $table->addOnLoadError($this->buildJsOnLoadError());
        
        $inheritedOptions .= $table->buildJsInitOptionsHead();
        $inheritedOptions = trim($inheritedOptions, "\r\n\t,");
        
        $requiredScript = $widget->isRequired() ? ', required:true' : '';
        $disabledScript = $widget->isDisabled() ? ', disabled:true' : '';
        $multiSelectScript = $widget->getMultiSelect() ? ', multiple: true' : '';
        
        // Das entspricht dem urspruenglichen Verhalten. Filter-Referenzen werden beim Loeschen eines
        // Elements nicht geleert, sondern nur aktualisiert.
        $filterSetterUpdateScript = $widget->getLazyLoadingGroupId() ? '
                                // Der eigene Wert wird geloescht.
                                ' . $this->getId() . '_jquery.data("_clearFilterSetterUpdate", true);
                                // Loeschen der verlinkten Elemente wenn der Wert manuell geloescht wird.
                                ' . $this->getId() . '_jquery.data("_otherClearFilterSetterUpdate", true);' : '
                                // Die Updates der Filter-Links werden an dieser Stelle unterdrueckt und
                                // nur einmal nach dem value-Setter update onLoadSuccess ausgefuehrt.
                                ' . $this->getId() . '_jquery.data("_suppressFilterSetterUpdate", true);';
        
        $reloadOnSelectSkript = $widget->getLazyLoadingGroupId() ? '
                                // Update des eigenen Widgets.
                                ' . $this->getId() . '_jquery.data("_filterSetterUpdate", true);
                                ' . $this->getId() . '_datagrid.datagrid("reload");' : '';
        
        $output .= $inheritedOptions . <<<JS

                        , textField:"{$this->getWidget()->getTextColumn()->getDataColumnName()}"
                        , mode: "remote"
                        , method: "post"
                        , delay: 600
                        , panelWidth:600
                        {$requiredScript}
                        {$disabledScript}
                        {$multiSelectScript}
                        , onChange: function(newValue, oldValue) {
                            // Wird getriggert durch manuelle Eingabe oder durch setValue().
                            {$this->buildJsDebugMessage('onChange')}
                            
                            function getValueArray(value) {
                                var valueArray;
                                switch ($.type(value)) {
                                    case "number":
                                        valueArray = [value]; break;
                                    case "string":
                                        if (value) {
                                            valueArray = $.map(value.split(","), $.trim); break;
                                        } else {
                                            valueArray = []; break;
                                        }
                                    case "array":
                                        valueArray = value; break;
                                    default:
                                        valueArray = [];
                                }
                                return valueArray;
                            }
                            
                            // newValue kann eine Number/String sein oder ein Array (bei multi_select).
                            var newValueArray = getValueArray(newValue);
                            var oldValueArray = getValueArray(oldValue);
                            
                            // Akualisieren von currentText. Es gibt keine andere gute Moeglichkeit
                            // an den gerade eingegebenen Text zu kommen (combogrid("getText") liefert
                            // keinen aktuellen Wert). Funktion dieses Wertes siehe onHidePanel.
                            {$this->getId()}_jquery.data("_currentText", newValueArray.join());
                            if (newValueArray.length == 0) {
                                {$this->getId()}_jquery.data("_lastValidValue", "");
                                // Das Objekt hatte einen Wert, der geloescht wurde.
                                {$this->getId()}_jquery.data("_clear", true);
                                {$filterSetterUpdateScript}
                                {$this->buildJsFunctionPrefix()}onChange();
                            }
                            // Anschließend an onChange wird neu geladen -> onBeforeLoad
                        }
                        , onSelect: function(index, row) {
                            // Wird getriggert durch manuelle Auswahl einer Zeile oder durch
                            // setSelection().
                            {$this->buildJsDebugMessage('onSelect')}
                            // Aktualisieren von lastValidValue. Loeschen von currentText. Funktion
                            // dieser Werte siehe onHidePanel.
                            //{$this->getId()}_jquery.data("_lastValidValue", {$this->getId()}_jquery.combogrid("getValues").join());
                            {$this->getId()}_jquery.data("_lastValidValue", {$this->buildJsFunctionPrefix()}valueGetter());
                            {$this->getId()}_jquery.data("_currentText", "");
                            
                            // Wichtig fuer lazy_loading_groups, da sonst ein sich widersprechender Filter-
                            // satz hergestellt werden kann. Gibt aber Probleme bei multi_select, da nach
                            // jeder Auswahl neu geladen wird und die restlichen Optionen verschwinden.
                            if ({$this->getId()}_jquery.data("_suppressReloadOnSelect")) {
                                // Verhindert das neu Laden onSelect, siehe onLoadSuccess (autoselectsinglesuggestion)
                                {$this->getId()}_jquery.removeData("_suppressReloadOnSelect");
                            } else {
                                {$reloadOnSelectSkript}
                            }
                            
                            //Referenzen werden aktualisiert.
                            {$this->buildJsFunctionPrefix()}onChange();
                        }
                        , onShowPanel: function() {
                            // Wird firstLoad verhindert, wuerde man eine leere Tabelle sehen. Um das zu
                            // verhindern wird die Tabelle hier neu geladen, falls sie leer ist.
                            // Update: Wird immer noch doppelt geladen, wenn anfangs eine manuelle Eingabe
                            // gemacht wird -> onChange (-> Laden), onShowPanel (-> Laden)
                            {$this->buildJsDebugMessage('onShowPanel')}
                            if ({$this->getId()}_jquery.data("_firstLoad")) {
                                {$this->getId()}_datagrid.datagrid("reload");
                            }
                        }
                        , onHidePanel: function() {
                            {$this->buildJsDebugMessage('onHidePanel')}
                            var selectedRows = {$this->getId()}_datagrid.datagrid("getSelections");
                            // lastValidValue enthaelt den letzten validen Wert der InputComboTable.
                            var lastValidValue = {$this->getId()}_jquery.data("_lastValidValue");
                            var currentValue = {$this->getId()}_jquery.combogrid("getValues").join();
                            // currentText enthaelt den seit der letzten validen Auswahl in die InputComboTable eingegebenen Text,
                            // d.h. ist currentText nicht leer wurde Text eingegeben aber noch keine Auswahl getroffen.
                            var currentText = {$this->getId()}_jquery.data("_currentText");
                            
                            // Das Panel wird automatisch versteckt, wenn man das Eingabefeld verlaesst.
                            // Wurde zu diesem Zeitpunkt seit der letzten Auswahl Text eingegeben, aber
                            // kein Eintrag ausgewaehlt, dann wird der letzte valide Zustand wiederher-
                            // gestellt.
                            if (selectedRows.length == 0 && currentText) {
                                if (lastValidValue){
                                    {$this->getId()}_jquery.data("_currentText", "");
                                    {$this->buildJsFunctionPrefix()}valueSetter(lastValidValue);
                                } else {
                                    {$this->getId()}_jquery.data("_currentText", "");
                                    {$this->buildJsFunctionPrefix()}clear(true);
                                    if (currentValue != lastValidValue) {
                                        {$this->getId()}_datagrid.datagrid("reload");
                                    }
                                }
                            }
                        }
                        , onDestroy: function() {
                            // Wird leider nicht getriggert, sonst waere das eine gute Moeglichkeit
                            // die globalen Variablen nur nach Bedarf zu initialisieren.
                            {$this->buildJsDebugMessage('onDestroy')}
                            
                            delete {$this->getId()}_jquery;
                            delete {$this->getId()}_datagrid;
                        }
JS;
        return $output;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJsValueGetter()
     */
    function buildJsValueGetter($column = null, $row = null)
    {
        $params = $column ? '"' . $column . '"' : '';
        $params = $row ? ($params ? $params . ', ' . $row : $row) : $params;
        return $this->buildJsFunctionPrefix() . 'valueGetter(' . $params . ')';
    }

    /**
     * Creates a JavaScript function which returns the value of the element.
     *
     * @return string
     */
    function buildJsValueGetterFunction()
    {
        $widget = $this->getWidget();
        $uidColumnName = $widget->getTable()->getUidColumn()->getDataColumnName();
        
        if ($widget->getMultiSelect()) {
            $value_getter = <<<JS

                            var resultArray = [];
                            for (i = 0; i < selectedRows.length; i++) {
                                // Wird die Spalte vom Server angefordert, das Attribut des Objekts existiert
                                // aber nicht, wird false zurueckgegeben (Booleans werden als "0"/"1" zurueckgegeben).
                                if (selectedRows[i][column] == undefined || selectedRows[i][column] === false) {
                                    if (window.console) { console.warn("The non-existing column \"" + column + "\" was requested from element \"{$this->getId()}\""); }
                                    resultArray.push("");
                                } else {
                                    resultArray.push(selectedRows[i][column]);
                                }
                            }
                            return resultArray.join();
JS;
        } else {
            $value_getter = <<<JS

                            // Wird die Spalte vom Server angefordert, das Attribut des Objekts existiert
                            // aber nicht, wird false zurueckgegeben (Booleans werden als "0"/"1" zurueckgegeben).
                            if (selectedRows[0][column] == undefined || selectedRows[0][column] === false) {
                                if (window.console) { console.warn("The non-existing column \"" + column + "\" was requested from element \"{$this->getId()}\""); }
                                return "";
                            } else {
                                return selectedRows[0][column];
                            }
JS;
        }
        
        $output = <<<JS

                function {$this->buildJsFunctionPrefix()}valueGetter(column, row){
                    // Der value-Getter wird in manchen Faellen aufgerufen, bevor die globalen
                    // Variablen definiert sind. Daher hier noch einmal initialisieren.
                    {$this->buildJsFunctionPrefix()}initGlobals();
                    
                    {$this->buildJsDebugMessage('valueGetter()')}
                    
                    // Wird kein Spaltenname uebergeben, wird die UID-Spalte zurueckgegeben.
                    if (!column) {
                        column = "{$uidColumnName}";
                    }
                    
                    if ({$this->getId()}_jquery.data("combogrid")) {
                        var selectedRows = {$this->getId()}_datagrid.datagrid("getSelections");
                        if (selectedRows.length > 0) {
                            {$value_getter}
                        } else if (column == "{$uidColumnName}") {
                            // Wurde durch den prefill nur value und text gesetzt, aber noch
                            // nichts geladen (daher auch keine Auswahl) dann wird der gesetzte
                            // value zurueckgegeben wenn die OID-Spalte angefragt wird (wichtig
                            // fuer das Funktionieren von Filtern bei initialem Laden).
                            return {$this->getId()}_jquery.combogrid("getValues").join();
                        } else {
                            return "";
                        }
                    } else {
                        if (column == "{$uidColumnName}") {
                            return {$this->getId()}_jquery.val();
                        } else {
                            return "";
                        }
                    }
                }
                
JS;
        
        return $output;
    }

    /**
     * The JS value setter for EasyUI combogrids is a custom function defined in euiInputComboTable::buildJs() - it only needs to be called here.
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJsValueSetter($value)
     */
    function buildJsValueSetter($value)
    {
        return $this->buildJsFunctionPrefix() . 'valueSetter(' . $value . ')';
    }

    /**
     * Creates a JavaScript function which sets the value of the element.
     *
     * @return string
     */
    function buildJsValueSetterFunction()
    {
        $widget = $this->getWidget();
        
        if ($widget->getMultiSelect()) {
            $value_setter = <<<JS
                            {$this->getId()}_jquery.combogrid("setValues", valueArray);
JS;
        } else {
            $value_setter = <<<JS
                            if (valueArray.length <= 1) {
                                {$this->getId()}_jquery.combogrid("setValues", valueArray);
                            }
JS;
        }
        
        $output = <<<JS

                function {$this->buildJsFunctionPrefix()}valueSetter(value){
                    {$this->buildJsDebugMessage('valueSetter()')}
                    var valueArray;
                    if ({$this->getId()}_jquery.data("combogrid")) {
                        if (value) {
                            switch ($.type(value)) {
                                case "number":
                                    valueArray = [value]; break;
                                case "string":
                                    valueArray = $.map(value.split(","), $.trim); break;
                                case "array":
                                    valueArray = value; break;
                                default:
                                    valueArray = [];
                            }
                        } else {
                            valueArray = [];
                        }
                        if (!{$this->getId()}_jquery.combogrid("getValues").equals(valueArray)) {
                            //onChange wird getriggert
                            {$value_setter}
                            
                            {$this->getId()}_jquery.data("_lastValidValue", valueArray.join());
                            {$this->getId()}_jquery.data("_valueSetterUpdate", true);
                            {$this->getId()}_datagrid.datagrid("reload");
                        }
                    } else {
                        {$this->getId()}_jquery.val(value).trigger("change");
                    }
                }
                
JS;
        
        return $output;
    }

    /**
     * Creates a JavaScript function which sets the value of the element.
     *
     * @return string
     */
    function buildJsOnChangeFunction()
    {
        $widget = $this->getWidget();
        
        $output = <<<JS

                function {$this->buildJsFunctionPrefix()}onChange(){
                    {$this->buildJsDebugMessage('onChange()')}
                    // Diese Werte koennen gesetzt werden damit, wenn der Wert der InputComboTable
                    // geaendert wird, nur ein Teil oder gar keine verlinkten Elemente aktualisiert
                    // werden.
                    var suppressFilterSetterUpdate = false, clearFilterSetterUpdate = false, suppressAllUpdates = false, suppressLazyLoadingGroupUpdate = false;
                    if ({$this->getId()}_jquery.data("_otherSuppressFilterSetterUpdate")){
                        // Es werden keine Filter-Links aktualisiert.
                        {$this->getId()}_jquery.removeData("_otherSuppressFilterSetterUpdate");
                        suppressFilterSetterUpdate = true;
                    }
                    if ({$this->getId()}_jquery.data("_otherClearFilterSetterUpdate")){
                        // Filter-Links werden geleert.
                        {$this->getId()}_jquery.removeData("_otherClearFilterSetterUpdate");
                        clearFilterSetterUpdate = true;
                    }
                    if ({$this->getId()}_jquery.data("_otherSuppressAllUpdates")){
                        // Weder Werte-Links noch Filter-Links werden aktualisiert.
                        {$this->getId()}_jquery.removeData("_otherSuppressAllUpdates");
                        suppressAllUpdates = true;
                    }
                    if ({$this->getId()}_jquery.data("_otherSuppressLazyLoadingGroupUpdate")){
                        // Die LazyLoadingGroup wird nicht aktualisiert.
                        {$this->getId()}_jquery.removeData("_otherSuppressLazyLoadingGroupUpdate");
                        suppressLazyLoadingGroupUpdate = true;
                    }
                    
                    if (!suppressAllUpdates) {
                        {$this->getOnChangeScript()}
                    }
                }
                
JS;
        
        return $output;
    }

    /**
     * Creates the JavaScript-Code which is executed before loading the autosuggest-
     * data.
     * If a value was set programmatically a single filter for this value is
     * added to the request to display the label properly. Otherwise the filters
     * which were defined on the widget are added to the request. The filters are
     * removed after loading as their values can change because of live-references.
     *
     * @return string
     */
    function buildJsOnBeforeload()
    {
        $widget = $this->getWidget();
        $valueFilterParam = UrlDataType::urlEncode($this->getTemplate()->getUrlFilterPrefix().$widget->getValueColumn()->getAttributeAlias());
        
        // If the value is set data is loaded from the backend. Same if also value-text is set, because otherwise
        // live-references don't work at the beginning. If no value is set, loading from the backend is prevented.
        // The trouble here is, that if the first loading is prevented, the next time the user clicks on the dropdown button,
        // an empty table will be shown, because the last result is cached. To fix this, we bind a reload of the table to
        // onShowPanel in case the grid is empty (see above).
        if (! is_null($this->getValueWithDefaults()) && $this->getValueWithDefaults() !== '') {
            if (trim($widget->getValueText())) {
                // If the text is already known, set it and prevent initial backend request
                $widget_value_text = str_replace('"', '\"', trim($widget->getValueText()));
                $first_load_script = <<<JS

                        {$this->getId()}_jquery.combogrid("setText", "{$widget_value_text}");
                        {$this->getId()}_jquery.data("_lastValidValue", "{$this->getValueWithDefaults()}");
                        {$this->getId()}_jquery.data("_currentText", "");
                        return false;
JS;
            } else {
                $first_load_script = <<<JS

                        {$this->getId()}_jquery.data("_lastValidValue", "{$this->getValueWithDefaults()}");
                        {$this->getId()}_jquery.data("_currentText", "");
                        {$this->getId()}_jquery.data("_valueSetterUpdate", true);
                        currentFilterSet["{$valueFilterParam}"] = "{$this->getValueWithDefaults()}";
JS;
            }
        } else {
            // If no value set, just supress initial autoload
            $first_load_script = <<<JS

                        {$this->getId()}_jquery.data("_lastValidValue", "");
                        {$this->getId()}_jquery.data("_currentText", "");
                        return false;
JS;
        }
        
        // Run the data getter of the (unrendered) DataConfigurator widget to get the data
        // parameter with filters, sorters, etc.
        $dataParam = 'currentFilterSet.data = ' . $this->getTemplate()->getElement($widget->getTable()->getConfiguratorWidget())->buildJsDataGetter(null, true);
        // Beim Leeren eines Widgets in einer in einer lazy-loading-group wird kein Filter gesetzt,
        // denn alle Filter sollten leer sein (alle Elemente der Gruppe leer). Beim Leeren eines
        // Widgets ohne Gruppe werden die normalen Filter gesetzt.
        $clearFiltersParam = $widget->getLazyLoadingGroupId() ? '' : $dataParam;
        // Add value filter (to show proper label for a set value)
        $valueFilterParam = 'currentFilterSet["' . $valueFilterParam . '"] = ' . $this->getId() . '_jquery.combogrid("getValues").join();';
        
        // firstLoadScript: enthaelt Anweisungen, die nur beim ersten Laden ausgefuehrt
        // werden sollen (Initialisierung)
        // dataParam: fuegt die gesetzten Filter zur Anfrage hinzu
        // valueFilterParam: fuegt einen Filter zur Anfrage hinzu, welcher auf dem
        // aktuell gesetzten Wert beruht
        // clearFiltersParam: fuegt Filter zur Anfrage hinzu, welche beim Leeren des
        // Objekts gelten sollen
        
        $output = <<<JS

                    // OnBeforeLoad ist das erste Event, das nach der Erzeugung des Objekts getriggert
                    // wird. Daher werden hier globale Variablen initialisiert (_datagrid kann vorher
                    // nicht initialisiert werden, da das combogrid-Objekt noch nicht existiert).
                    {$this->buildJsFunctionPrefix()}initGlobals();
                    
                    {$this->buildJsDebugMessage('onBeforeLoad')}
                    
                    // Wird eine Eingabe gemacht, dann aber keine Auswahl getroffen, ist bei der naechsten
                    // Anfrage param.q noch gesetzt (param eigentlich nur Kopie???). Deshalb hier loeschen.
                    delete param.q;
                    
                    if (!{$this->getId()}_jquery.data("_lastFilterSet")) { {$this->getId()}_jquery.data("_lastFilterSet", {}); }
                    var currentFilterSet = {page: param.page, rows: param.rows, sort: param.sort, order: param.order};
                    
                    if ({$this->getId()}_jquery.data("_firstLoad") == undefined){
                        {$this->getId()}_jquery.data("_firstLoad", true);
                    } else if ({$this->getId()}_jquery.data("_firstLoad")){
                        {$this->getId()}_jquery.data("_firstLoad", false);
                    }
                    
                    if ({$this->getId()}_jquery.data("_valueSetterUpdate")) {
                        param._valueSetterUpdate = true;
                        {$valueFilterParam}
                    } else if ({$this->getId()}_jquery.data("_clearFilterSetterUpdate")) {
                        param._clearFilterSetterUpdate = true;
                        {$clearFiltersParam}
                    } else if ({$this->getId()}_jquery.data("_filterSetterUpdate")) {
                        param._filterSetterUpdate = true;
                        {$dataParam}
                        {$valueFilterParam}
                    } else if ({$this->getId()}_jquery.data("_firstLoad")) {
                        param._firstLoad = true;
                        {$first_load_script}
                    } else {
                        currentFilterSet.q = {$this->getId()}_jquery.data("_currentText");
                        {$dataParam}
                    }
                    
                    // Die Filter der gegenwaertigen Anfrage werden mit den Filtern der letzten Anfrage
                    // verglichen. Sind sie identisch und wurden die zuletzt geladenen Daten nicht ver-
                    // aendert, wird die Anfrage unterbunden, denn das Resultat waere das gleiche.
                    if ((JSON.stringify(currentFilterSet) === JSON.stringify({$this->getId()}_jquery.data("_lastFilterSet"))) &&
                            !({$this->getId()}_jquery.data("_resultSetChanged"))) {
                        // Suchart entfernen, sonst ist sie beim naechsten Mal noch gesetzt
                        {$this->getId()}_jquery.removeData("_valueSetterUpdate");
                        {$this->getId()}_jquery.removeData("_clearFilterSetterUpdate");
                        {$this->getId()}_jquery.removeData("_filterSetterUpdate");
                        {$this->getId()}_jquery.removeData("_clear");
                        
                        return false;
                    } else {
                        {$this->getId()}_jquery.data("_lastFilterSet", currentFilterSet);
                        $.extend(param, currentFilterSet);
                    }
JS;
        /*
         * FIXME how to make multiselects search for every text in the list separately. The trouble is,
         * the combotable seems to drop _all_ it's values once you continue typing. It will only restore
         * them if the returned resultset contains them too.
         * if ($widget->getMultiSelect()){
         * $output .= '
         * if (param.q.indexOf("' . $widget->getMultiSelectTextDelimiter() . '") !== -1){
         * // The idea here was to send a list of texts for an IN-query. This returns no results though, as
         * // the SQL "IN" expects exact matches, no LIKEs
         * //param.q = "["+param.q;
         *
         * // Here the q-parameter was to be split into "old" and new part and the search would only be done with the
         * // new part. This did not work because the old values would get lost and be replaced by the text. To cope
         * // with this the ID filter was to be used, but it would add an AND to the query, not an OR.
         * //param.q = param.q.substring(param.q.lastIndexOf("' . $widget->getMultiSelectTextDelimiter() . '") + 1);
         * //param.filter_' . $widget->getValueColumn()->getDataColumnName() . ' = $("#' . $this->getId() .'").data("lastValidValue");
         * }
         * ';
         * }
         */
        
        return $output;
    }

    /**
     * Creates javascript-code which is executed after the successful loading of auto-
     * suggest-data.
     * If autoselect_single_suggestion is true, a single return value
     * from autosuggest is automatically selected.
     *
     * @return string
     */
    function buildJsOnLoadSucess()
    {
        $widget = $this->getWidget();
        
        $uidColumnName = $widget->getTable()->getUidColumn()->getDataColumnName();
        $textColumnName = $widget->getTextColumn()->getDataColumnName();
        
        $suppressLazyLoadingGroupUpdateScript = $widget->getLazyLoadingGroupId() ? '
                                // Ist das Widget in einer lazy-loading-group, werden keine Filter-Referenzen aktualisiert,
                                // denn alle Elemente der Gruppe werden vom Orginalobjekt bedient.
                                if (suppressLazyLoadingGroupUpdate) {
                                    ' . $this->getId() . '_jquery.data("_otherSuppressLazyLoadingGroupUpdate", true);
                                }' : '';
        
        $output = <<<JS

                    {$this->buildJsDebugMessage('onLoadSuccess')}
                    var suppressAutoSelectSingleSuggestion = false;
                    var suppressLazyLoadingGroupUpdate = false;
                    
                    if ({$this->getId()}_jquery.data("_valueSetterUpdate")) {
                        // Update durch eine value-Referenz.
                        
                        {$this->getId()}_jquery.removeData("_valueSetterUpdate");
                        {$this->getId()}_jquery.removeData("_clearFilterSetterUpdate");
                        {$this->getId()}_jquery.removeData("_filterSetterUpdate");
                        {$this->getId()}_jquery.removeData("_clear");
                        
                        // Nach einem Value-Setter-Update wird der Text neu gesetzt um das Label ordentlich
                        // anzuzeigen und das onChange-Skript wird ausgefuehrt.
                        var selectedRows = {$this->getId()}_datagrid.datagrid("getSelections");
                        if (selectedRows.length > 0) {
                            {$this->getId()}_jquery.combogrid("setText", {$this->buildJsFunctionPrefix()}valueGetter("{$textColumnName}"));
                        }
                        
                        {$this->buildJsFunctionPrefix()}onChange();
                    } else if ({$this->getId()}_jquery.data("_clearFilterSetterUpdate")) {
                        // Leeren durch eine filter-Referenz.
                        
                        {$this->getId()}_jquery.removeData("_valueSetterUpdate");
                        {$this->getId()}_jquery.removeData("_clearFilterSetterUpdate");
                        {$this->getId()}_jquery.removeData("_filterSetterUpdate");
                        {$this->getId()}_jquery.removeData("_clear");
                        
                        {$this->buildJsFunctionPrefix()}clear(false);
                        
                        // Neu geladen werden muss nicht, denn die Filter waren beim vorangegangenen Laden schon
                        // entsprechend gesetzt.
                        
                        // Wurde das Widget manuell geloescht, soll nicht wieder automatisch der einzige Suchvorschlag
                        // ausgewaehlt werden.
                        suppressAutoSelectSingleSuggestion = true;
                    } else if ({$this->getId()}_jquery.data("_filterSetterUpdate")) {
                        // Update durch eine filter-Referenz.
                        
                        // Ergibt die Anfrage bei einem FilterSetterUpdate keine Ergebnisse waehrend ein Wert
                        // gesetzt ist, widerspricht der gesetzte Wert wahrscheinlich den gesetzten Filtern.
                        // Deshalb wird der Wert der InputComboTable geloescht und anschliessend neu geladen.
                        var rows = {$this->getId()}_datagrid.datagrid("getData");
                        if (rows["total"] == 0 && {$this->buildJsFunctionPrefix()}valueGetter()) {
                            {$this->buildJsFunctionPrefix()}clear(true);
                            {$this->getId()}_datagrid.datagrid("reload");
                        }
                        
                        {$this->getId()}_jquery.removeData("_valueSetterUpdate");
                        {$this->getId()}_jquery.removeData("_clearFilterSetterUpdate");
                        {$this->getId()}_jquery.removeData("_filterSetterUpdate");
                        {$this->getId()}_jquery.removeData("_clear");
                        
                        // Wurde das Widget ueber eine Filter-Referenz befuellt (lazy-loading-group), werden
                        // keine Filter-Referenzen aktualisiert, denn alle Elemente der Gruppe werden vom
                        // Orginalobjekt bedient (wurde es hingegen manuell befuellt (autoselect) muessen
                        // die Filter-Referenzen bedient werden).
                        suppressLazyLoadingGroupUpdate = true;
                    } else if ({$this->getId()}_jquery.data("_clear")) {
                        // Loeschen des Wertes
                        
                        {$this->getId()}_jquery.removeData("_valueSetterUpdate");
                        {$this->getId()}_jquery.removeData("_clearFilterSetterUpdate");
                        {$this->getId()}_jquery.removeData("_filterSetterUpdate");
                        {$this->getId()}_jquery.removeData("_clear");
                        
                        // Wurde das Widget manuell geloescht, soll nicht wieder automatisch der einzige Suchvorschlag
                        // ausgewaehlt werden.
                        suppressAutoSelectSingleSuggestion = true;
                    }
                    
                    // Das resultSet wurde neu geladen und ist daher unveraendert. Ein erneutes Laden mit
                    // identischem filterSet kann unterbunden werden (siehe onBeforeLoad).
                    {$this->getId()}_jquery.data("_resultSetChanged", false);
JS;
        
        if ($widget->getAutoselectSingleSuggestion()) {
            $output .= <<<JS

                    if (!suppressAutoSelectSingleSuggestion) {
                        // Automatisches Auswaehlen des einzigen Suchvorschlags.
                        var rows = {$this->getId()}_datagrid.datagrid("getData");
                        if (rows["total"] == 1) {
                            var selectedRows = {$this->getId()}_datagrid.datagrid("getSelections");
                            if (selectedRows.length == 0 || selectedRows.length > 1 || selectedRows[0]["{$uidColumnName}"] != rows["rows"][0]["{$uidColumnName}"]) {
                                // Fuer multi_select werden erst alle angewaehlten Werte entfernt.
                                {$this->buildJsFunctionPrefix()}clear(true);
                                {$suppressLazyLoadingGroupUpdateScript}
                                // Beim Autoselect wurde ja zuvor schon geladen und es gibt nur noch einen Vorschlag
                                // im Resultat (im Gegensatz zur manuellen Auswahl eines Ergebnisses aus einer Liste).
                                {$this->getId()}_jquery.data("_suppressReloadOnSelect", true);
                                // onSelect wird getriggert
                                {$this->getId()}_datagrid.datagrid("selectRow", 0);
                                {$this->getId()}_jquery.combogrid("setText", rows["rows"][0]["{$textColumnName}"]);
                                {$this->getId()}_jquery.combogrid("hidePanel");
                            }
                        }
                    }
                    
JS;
        }
        
        return $output;
    }

    /**
     * Creates javascript-code which is executed after the erroneous loading of auto-
     * suggest-data.
     *
     * @return string
     */
    function buildJsOnLoadError()
    {
        $widget = $this->getWidget();
        
        $output = <<<JS

                    {$this->buildJsDebugMessage('onLoadError')}
                    
                    {$this->getId()}_jquery.removeData("_valueSetterUpdate");
                    {$this->getId()}_jquery.removeData("_clearFilterSetterUpdate");
                    {$this->getId()}_jquery.removeData("_filterSetterUpdate");
                    {$this->getId()}_jquery.removeData("_clear");
JS;
        
        return $output;
    }

    /**
     * Creates a javascript-function which empties the object.
     * If the object had a value
     * before, onChange is triggered by clearing it. If suppressAllUpdates = true is
     * passed to the function, linked elements are not updated by clearing the object.
     * This behavior is usefull, if the object should really just be cleared.
     *
     * @return string
     */
    function buildJsClearFunction()
    {
        $widget = $this->getWidget();
        
        $output = <<<JS

                function {$this->buildJsFunctionPrefix()}clear(suppressAllUpdates) {
                    {$this->buildJsDebugMessage('clear()')}
                    
                    // Bestimmt ob durch das Leeren andere verlinkte Elemente aktualisiert werden sollen. 
                    {$this->getId()}_jquery.data("_otherSuppressAllUpdates", suppressAllUpdates);
                    // Beim Leeren wird die LazyLoadingGroup (wenn es eine gibt) nicht aktualisiert.
                    {$this->getId()}_jquery.data("_otherSuppressLazyLoadingGroupUpdate", true);
                    // Durch das Leeren aendert sich das resultSet und es sollte das naechste Mal neu geladen
                    // werden, auch wenn sich das Filterset nicht geaendert hat (siehe onBeforeLoad).
                    {$this->getId()}_jquery.data("_resultSetChanged", true);
                    // Triggert onChange, wenn vorher ein Element ausgewaehlt war.
                    {$this->getId()}_jquery.combogrid("clear");
                    // Wurde das Widget bereits manuell geleert, wird mit clear kein onChange getriggert und
                    // _otherSuppressAllUpdates nicht entfernt. Wird clear mit _otherSuppressAllUpdates
                    // gestartet, dann ist hinterher _clearFilterSetterUpdate gesetzt. Daher werden hier
                    // vorsichtshalber _otherSuppressAllUpdates und _clearFilterSetterUpdate manuell geloescht.
                    {$this->getId()}_jquery.removeData("_otherSuppressAllUpdates");
                    {$this->getId()}_jquery.removeData("_otherSuppressLazyLoadingGroupUpdate");
                    {$this->getId()}_jquery.removeData("_clearFilterSetterUpdate");
                }
JS;
        return $output;
    }

    function getJsDebugLevel()
    {
        return $this->js_debug_level;
    }

    /**
     * Determines the detail-level of the debug-messages which are written to the browser-
     * console.
     * 0 = off, 1 = low, 2 = medium, 3 = high detail-level (default: 0)
     *
     * @param integer|string $value            
     * @return \exface\JEasyUiTemplate\Templates\Elements\euiInputComboTable
     */
    function setJsDebugLevel($value)
    {
        if (is_int($value)) {
            $this->js_debug_level = $value;
        } else if (is_string($value)) {
            $this->js_debug_level = intval($value);
        } else {
            throw new InvalidArgumentException('Can not set js_debug_level for "' . $this->getId() . '": the argument passed to set_js_debug_level() is neither an integer nor a string!');
        }
        return $this;
    }

    /**
     * Creates javascript-code that writes a debug-message to the browser-console.
     *
     * @param string $source            
     * @return string
     */
    function buildJsDebugMessage($source)
    {
        switch ($this->getJsDebugLevel()) {
            case 0:
                $output = '';
                break;
            case 1:
            case 2:
                $output = <<<JS
                if (window.console) { console.debug(Date.now() + "|{$this->getId()}.{$source}"); }
JS;
                break;
            case 3:
                $output = <<<JS
                if (window.console) { console.debug(Date.now() + "|{$this->getId()}.{$source}|" + {$this->buildJsFunctionPrefix()}debugDataToString()); }
JS;
                break;
            default:
                $output = '';
        }
        return $output;
    }

    /**
     * Creates a javascript-function, which returns a string representation of the content
     * of private variables which are stored in the data-object of the element and which
     * are important for the function of the object.
     * It is required for debug-messages with
     * a high detail-level.
     *
     * @return string
     */
    function buildJsDebugDataToStringFunction()
    {
        $output = <<<JS
		
                function {$this->buildJsFunctionPrefix()}debugDataToString() {
                    var currentValue = {$this->getId()}_jquery.data("combogrid") ? {$this->getId()}_jquery.combogrid("getValues").join() : {$this->getId()}_jquery.val();;
                    var output =
                        "_valueSetterUpdate: " + {$this->getId()}_jquery.data("_valueSetterUpdate") + ", " +
                        "_filterSetterUpdate: " + {$this->getId()}_jquery.data("_filterSetterUpdate") + ", " +
                        "_clearFilterSetterUpdate: " + {$this->getId()}_jquery.data("_clearFilterSetterUpdate") + ", " +
                        "_firstLoad: " + {$this->getId()}_jquery.data("_firstLoad") + ", " +
                        "_otherSuppressFilterSetterUpdate: " + {$this->getId()}_jquery.data("_otherSuppressFilterSetterUpdate") + ", " +
                        "_otherClearFilterSetterUpdate: " + {$this->getId()}_jquery.data("_otherClearFilterSetterUpdate") + ", " +
                        "_otherSuppressAllUpdates: " + {$this->getId()}_jquery.data("_otherSuppressAllUpdates") + ", " +
                        "_otherSuppressLazyLoadingGroupUpdate: " + {$this->getId()}_jquery.data("_otherSuppressLazyLoadingGroupUpdate") + ", " +
                        "_suppressReloadOnSelect: " + {$this->getId()}_jquery.data("_suppressReloadOnSelect") + ", " +
                        "_currentText: " + {$this->getId()}_jquery.data("_currentText") + ", " +
                        "_lastValidValue: " + {$this->getId()}_jquery.data("_lastValidValue") + ", " +
                        "currentValue: " + currentValue + ", " +
                        "_lastFilterSet: "+ JSON.stringify({$this->getId()}_jquery.data("_lastFilterSet")) + ", " +
                        "_resultSetChanged: " + {$this->getId()}_jquery.data("_resultSetChanged");
                    return output;
                }
JS;
        return $output;
    }

    /**
     *
     * @return string
     */
    function buildJsInitGlobalsFunction()
    {
        $output = <<<JS

                function {$this->buildJsFunctionPrefix()}initGlobals() {
                    window.{$this->getId()}_jquery = $("#{$this->getId()}");
                    if ({$this->getId()}_jquery.data("combogrid")) {
                        window.{$this->getId()}_datagrid = {$this->getId()}_jquery.combogrid("grid");
                    }
                }
JS;
        return $output;
    }
    
    /**
     *
     * @return boolean
     */
    protected function isLazyLoading()
    {
        return $this->getWidget()->getLazyLoading(true);
    }
}
?>