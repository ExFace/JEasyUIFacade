<?php
namespace exface\JEasyUiTemplate\Template\Elements;

use exface\Core\Widgets\ComboTable;
use exface\Core\Exceptions\Widgets\WidgetConfigurationError;
use exface\Core\Exceptions\InvalidArgumentException;

/**
 * 
 * @method ComboTable get_widget()
 * 
 * @author Andrej Kabachnik
 *
 */
class euiComboTable extends euiInput {
	
	private $js_debug = false;
	
	protected function init(){
		parent::init();
		$this->set_element_type('combogrid');
		$this->set_js_debug($this->get_template()->get_config()->get_option("JAVASCRIPT_DEBUG"));
		
		// Register onChange-Handler for Filters with Live-Reference-Values
		$widget = $this->get_widget();
		if ($widget->get_table()->has_filters()){
			foreach ($widget->get_table()->get_filters() as $fltr){
				if ($link = $fltr->get_value_widget_link()){
					$linked_element = $this->get_template()->get_element_by_widget_id($link->get_widget_id(), $this->get_page_id());
					
					$widget_lazy_loading_group_id = $widget->get_lazy_loading_group_id();
					$linked_element_lazy_loading_group_id = method_exists($linked_element->get_widget(), 'get_lazy_loading_group_id') ? $linked_element->get_widget()->get_lazy_loading_group_id() : '';
					// Gehoert das Widget einer Lazyloadinggruppe an, so darf es keine Filterreferenzen
					// zu Widgets außerhalb dieser Gruppe haben.
					if ($widget_lazy_loading_group_id && ($linked_element_lazy_loading_group_id != $widget_lazy_loading_group_id)) {
						throw new WidgetConfigurationError($widget, 'Widget "' . $widget->get_id() . '" in lazy-loading-group "' . $widget_lazy_loading_group_id . '" has a filter-reference to widget "' . $linked_element->get_widget()->get_id() . '" in lazy-loading-group "' . $linked_element_lazy_loading_group_id . '". Filter-references to widgets outside the own lazy-loading-group are not allowed.', '6V6C2HY');
					}
					
					$on_change_script = <<<JS

					// Ist suppressFilterSetterUpdate == true wird nicht neu geladen. Dadurch
					// wird unnoetiges neu Laden verhindert (siehe onChange).
					if (typeof suppressFilterSetterUpdate == "undefined" || !suppressFilterSetterUpdate) {
						$("#{$this->get_id()}").data("_filterSetterUpdate", true);
						if (typeof clearFilterSetterUpdate != "undefined" && clearFilterSetterUpdate) {
							$("#{$this->get_id()}").data("_clearFilterSetterUpdate", true);
						}
						$("#{$this->get_id()}").combogrid("grid").datagrid("reload");
					}
JS;
					
					$linked_element->add_on_change_script($on_change_script);
				}
			}
		}
	}
	
	protected function register_live_reference_at_linked_element(){
		$widget = $this->get_widget();
		
		if ($linked_element = $this->get_linked_template_element()){
			// Gehoert das Widget einer Lazyloadinggruppe an, so darf es keine Value-
			// Referenzen haben.
			$widget_lazy_loading_group_id = $widget->get_lazy_loading_group_id();
			if ($widget_lazy_loading_group_id) {
				throw new WidgetConfigurationError($widget, 'Widget "' . $widget->get_id() . '" in lazy-loading-group "' . $widget_lazy_loading_group_id . '" has a value-reference to widget "' . $linked_element->get_widget()->get_id() . '". Value-references to other widgets are not allowed.', '6V6C3AP');
			}
			
			$linked_element->add_on_change_script($this->build_js_live_reference());
		}
		return $this;
	}
	
	function generate_html(){
		/* @var $widget \exface\Core\Widgets\ComboTable */
		$widget = $this->get_widget();
		
		$value = $this->get_value_with_defaults();
		$name_script = $widget->get_attribute_alias() . ($widget->get_multi_select() ? '[]' : '');
		$required_script = $widget->is_required() ? 'required="true" ' : '';
		$disabled_script = $widget->is_disabled() ? 'disabled="disabled" ' : '';
		
		$output = <<<HTML

				<input style="height:100%;width:100%;"
					id="{$this->get_id()}" 
					name="{$name_script}" 
					value="{$value}"
					{$required_script}
					{$disabled_script} />
HTML;
		
		return $this->build_html_wrapper_div($output);
	}
	
	function generate_js(){
		$output = <<<JS

			if (!window.{$this->get_id()}) { window.{$this->get_id()} = $("#{$this->get_id()}"); }
			
			{$this->build_js_data_to_string_function()}
			
			{$this->get_id()}.combogrid({
				{$this->build_js_init_options()}
			});
			
JS;
		
		// Es werden JavaScript Value-Getter-/Setter- und OnChange-Funktionen fuer die ComboTable erzeugt,
		// um duplizierten Code zu vermeiden.
		$output .= <<<JS

			{$this->build_js_value_getter_function()}
			{$this->build_js_value_setter_function()}
			{$this->build_js_on_change_function()}
			{$this->build_js_clear_function()}
JS;
		
		// Es werden Dummy-Methoden fuer die Filter der DataTable hinter dieser ComboTable generiert. Diese
		// Funktionen werden nicht benoetigt, werden aber trotzdem vom verlinkten Element aufgerufen, da
		// dieses nicht entscheiden kann, ob das Filter-Input-Widget existiert oder nicht. Fuer diese Filter
		// existiert kein Input-Widget, daher existiert fuer sie weder HTML- noch JavaScript-Code und es
		// kommt sonst bei einem Aufruf der Funktion zu einem Fehler. 
		if ($this->get_widget()->get_table()->has_filters()) {
			foreach ($this->get_widget()->get_table()->get_filters() as $fltr) {
				$output .= <<<JS

			function {$this->get_template()->get_element($fltr->get_widget())->get_id()}_valueSetter(value){}
JS;
			}
		}
		
		// Add a clear icon to each combo grid - a small cross to the right, that resets the value
		// TODO The addClearBtn extension seems to break the setText method, so that it also sets the value. Perhaps we can find a better way some time
		// $output .= "$('#" . $this->get_id() . "').combogrid('addClearBtn', 'icon-clear');";
		
		return $output;
	}
	
	function build_js_init_options(){
		/* @var $widget \exface\Core\Widgets\ComboTable */
		$widget = $this->get_widget();
		/* @var $table \exface\JEasyUiTemplate\Template\Elements\DataTable */
		$table = $this->get_template()->get_element($widget->get_table());
		
		// Add explicitly specified values to every return data
		foreach ($widget->get_selectable_options() as $key => $value){
			if ($key === '' || is_null($key)) continue;
			$table->add_load_filter_script('data.rows.unshift({' . $widget->get_table()->get_uid_column()->get_data_column_name() . ': "' . $key . '", ' . $widget->get_text_column()->get_data_column_name() . ': "' . $value . '"});');
		}
		
		// Init the combogrid itself
		$inherited_options = '';
		if ($widget->get_lazy_loading() || (!$widget->get_lazy_loading() && $widget->is_disabled())){
			$inherited_options = $table->build_js_data_source();
		}
		$table->set_on_before_load($this->build_js_on_beforeload());
		$table->add_on_load_success($this->build_js_on_load_sucess());
		$table->add_on_load_error($this->build_js_on_load_error());
		
		$inherited_options .= $table->build_js_init_options_head();
		$inherited_options = trim($inherited_options, "\r\n\t,");
		
		$required_script = $widget->is_required() ? ', required:true' : '';
		$disabled_script = $widget->is_disabled() ? ', disabled:true' : '';
		$multi_select_script = $widget->get_multi_select() ? ', multiple: true' : '';
		
		
		$debug_on_change_script = $this->get_js_debug() ? 'console.debug(Date.now() + ": ' . $this->get_id() . '.onChange");' : '';
		$debug_on_select_script = $this->get_js_debug() ? 'console.debug(Date.now() + ": ' . $this->get_id() . '.onSelect");' : '';
		$debug_on_show_panel_script = $this->get_js_debug() ? 'console.debug(Date.now() + ": ' . $this->get_id() . '.onShowPanel");' : '';
		$debug_on_hide_panel_script = $this->get_js_debug() ? 'console.debug(Date.now() + ": ' . $this->get_id() . '.onHidePanel");' : '';
		
		$output .= $inherited_options . <<<JS

						, textField:"{$this->get_widget()->get_text_column()->get_data_column_name()}"
						, mode: "remote"
						, method: "post"
						, delay: 600
						, panelWidth:600
						{$required_script}
						{$disabled_script}
						{$multi_select_script}
						, onChange: function(newValue, oldValue) {
							{$debug_on_change_script}
							// Akualisieren von currentText. Es gibt keine andere gute Moeglichkeit
							// an den gerade eingegebenen Text zu kommen (combogrid("getText") liefert
							// keinen aktuellen Wert). Funktion dieses Wertes siehe onHidePanel.
							{$this->get_id()}.data("_currentText", newValue);
							if (!newValue) {
								{$this->get_id()}.data("_lastValidValue", "");
								// Loeschen der verlinkten Elemente wenn der Wert manuell geloescht wird.
								// Die Updates der Filter-Links werden an dieser Stelle unterdrueckt und
								// nur einmal nach dem value-Setter update onLoadSuccess ausgefuehrt.
								{$this->get_id()}.data("_clearFilterSetterUpdate", true);
								{$this->get_id()}.data("_otherClearFilterSetterUpdate", true);
								{$this->get_id()}_onChange();
							}
						}
						, onSelect: function(index, row) {
							{$debug_on_select_script}
							// Aktualisieren von lastValidValue. Loeschen von currentText. Funktion
							// dieser Werte siehe onHidePanel.
							{$this->get_id()}.data("_lastValidValue", row["{$widget->get_table()->get_uid_column()->get_data_column_name()}"]);
							{$this->get_id()}.data("_currentText", "");
							
							if ({$this->get_id()}.data("_suppressReloadOnSelect")) {
								{$this->get_id()}.removeData("_suppressReloadOnSelect");
							} else {
								{$this->get_id()}.data("_filterSetterUpdate", true);
								{$this->get_id()}_datagrid.datagrid("reload");
							}
							
							{$this->get_id()}_onChange();
						}
						, onShowPanel: function() {
							// Wird firstLoad verhindert, wuerde man eine leere Tabelle sehen. Um das zu
							// verhindern wird die Tabelle hier neu geladen, falls sie leer ist.
							{$debug_on_show_panel_script}
							if ({$this->get_id()}.data("_firstLoad")) {
								{$this->get_id()}_datagrid.datagrid("reload");
			                }
						}
						, onHidePanel: function() {
							{$debug_on_hide_panel_script}
							var selectedRow = {$this->get_id()}_datagrid.datagrid("getSelected");
							// lastValidValue enthaelt den letzten validen Wert der ComboTable.
							var lastValidValue = {$this->get_id()}.data("_lastValidValue");
							var currentValue = {$this->get_id()}.combogrid("getValues").join();
							// currentText enthaelt den seit der letzten validen Auswahl in die ComboTable eingegebenen Text,
							// d.h. ist currentText nicht leer wurde Text eingegeben aber noch keine Auswahl getroffen.
							var currentText = {$this->get_id()}.data("_currentText");
							
							// Das Panel wird automatisch versteckt, wenn man das Eingabefeld verlaesst.
							// Wurde zu diesem Zeitpunkt seit der letzten Auswahl Text eingegeben, aber
							// kein Eintrag ausgewaehlt, dann wird der letzte valide Zustand wiederher-
							// gestellt.
							if (selectedRow == null && currentText) {
								if (lastValidValue){
									{$this->get_id()}.data("_currentText", "");
									{$this->get_id()}_valueSetter(lastValidValue);
								} else {
									{$this->get_id()}.data("_currentText", "");
									{$this->get_id()}_clear(true);
									if (currentValue != lastValidValue) {
										{$this->get_id()}_datagrid.datagrid("reload");
									}
								}
							}
						}
JS;
		return $output;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\AbstractAjaxTemplate\Template\Elements\AbstractJqueryElement::build_js_value_getter()
	 */
	function build_js_value_getter($column = null, $row = null){
		$params = $column ? '"' . $column . '"' : '';
		$params = $row ? ($params ? $params . ', ' . $row : $row) : $params;
		return $this->get_id() . '_valueGetter(' . $params . ')';
	}
	
	/**
	 * Creates a JavaScript function which returns the value of the element.
	 * 
	 * @return string
	 */
	function build_js_value_getter_function(){
		$widget = $this->get_widget();
		
		if ($widget->get_multi_select()){
			$value_getter = <<<JS
						return {$this->get_id()}.combogrid("getValues").join();
JS;
		} else {
			$uidColumnName = $widget->get_table()->get_uid_column()->get_data_column_name();
			
			$value_getter = <<<JS
						if (column){
							var row = {$this->get_id()}_datagrid.datagrid("getSelected");
							if (row) {
								if (row[column] == undefined){
									{$this->get_id()}_datagrid.datagrid("reload");
								}
								return row[column];
							} else if (column == "{$uidColumnName}") {
								// Wurde durch den prefill nur value und text gesetzt, aber noch
								// nichts geladen (daher auch keine Auswahl) dann wird der gesetzte
								// value zurueckgegeben wenn die OID-Spalte angefragt wird (wichtig
								// fuer das Funktionieren von Filtern bei initialem Laden).
								return {$this->get_id()}.combogrid("getValues").join();
							} else {
								return "";
							}
						} else {
							return {$this->get_id()}.combogrid("getValues").join();
						}
JS;
		}
		
		$debug_value_getter_script = $this->get_js_debug() ? 'console.debug(Date.now() + ": ' . $this->get_id() . '.valueGetter()");' : ''; 
		
		$output = <<<JS
				
				function {$this->get_id()}_valueGetter(column, row){
					{$debug_value_getter_script}
					
					// Der value-Getter kann aufgerufen werden, wenn die globalen Variablen
					// noch nicht definiert sind.
					if (!window.{$this->get_id()}) { window.{$this->get_id()} = $("#{$this->get_id()}"); }
					
					if ({$this->get_id()}.data("combogrid")) {
						{$value_getter}
					} else {
						return {$this->get_id()}.val();
					}
				}
				
JS;
		
		return $output;
	}
	
	/**
	 * The JS value setter for EasyUI combogrids is a custom function defined in euiComboTable::generate_js() - it only needs to be called here.
	 * 
	 * {@inheritDoc}
	 * @see \exface\AbstractAjaxTemplate\Template\Elements\AbstractJqueryElement::build_js_value_setter($value)
	 */
	function build_js_value_setter($value){
		return $this->get_id() . '_valueSetter(' . $value . ')';
	}
	
	/**
	 * Creates a JavaScript function which sets the value of the element.
	 * 
	 * @return string
	 */
	function build_js_value_setter_function(){
		$widget = $this->get_widget();
		
		if ($widget->get_multi_select()) {
			$value_setter = <<<JS
							{$this->get_id()}.combogrid("setValues", valueArray);
JS;
		} else {
			$value_setter = <<<JS
							if (valueArray.length <= 1) {
								{$this->get_id()}.combogrid("setValues", valueArray);
							}
JS;
		}
								
		$debug_value_setter_script = $this->get_js_debug() ? 'console.debug(Date.now() + ": ' . $this->get_id() . '.valueSetter()");' : '';
		
		$output = <<<JS
				
				function {$this->get_id()}_valueSetter(value, suppressValueSetterUpdate = false){
					{$debug_value_setter_script}
					var valueArray;
					if ({$this->get_id()}.data("combogrid")) {
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
						if (!{$this->get_id()}.combogrid("getValues").equals(valueArray)) {
							{$value_setter}
							
							{$this->get_id()}.data("_lastValidValue", valueArray.join());
							
							if (!suppressValueSetterUpdate) {
								{$this->get_id()}.data("_valueSetterUpdate", true);
								{$this->get_id()}_datagrid.datagrid("reload");
							}
						}
					} else {
						{$this->get_id()}.val(value).trigger("change");
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
	function build_js_on_change_function(){
		$widget = $this->get_widget();
		
		$debug_on_change_script = $this->get_js_debug() ? 'console.debug(Date.now() + ": ' . $this->get_id() . '.onChange()");' : '';
		
		$output = <<<JS
				
				function {$this->get_id()}_onChange(){
					{$debug_on_change_script}
					// Diese Werte koennen gesetzt werden damit, wenn der Wert der ComboTable
					// geaendert wird, nur ein Teil oder gar keine verlinkten Elemente geupdated
					// werden.
					var suppressFilterSetterUpdate = false, clearFilterSetterUpdate = false, suppressAllUpdates = false;
					if ({$this->get_id()}.data("_otherSuppressFilterSetterUpdate")){
						// Es werden keine Filter-Links aktualisiert.
						{$this->get_id()}.removeData("_otherSuppressFilterSetterUpdate");
						suppressFilterSetterUpdate = true;
					}
					if ({$this->get_id()}.data("_otherClearFilterSetterUpdate")){
						// Filter-Links werden geleert.
						{$this->get_id()}.removeData("_otherClearFilterSetterUpdate");
						clearFilterSetterUpdate = true;
					}
					if ({$this->get_id()}.data("_otherSuppressAllUpdates")){
						// Weder Werte-Links noch Filter-Links werden aktualisiert.
						{$this->get_id()}.removeData("_otherSuppressAllUpdates");
						suppressAllUpdates = true;
					}
					
					if (!suppressAllUpdates) {
						{$this->get_on_change_script()}
					}
				}
				
JS;
		
		return $output;
	}
	
	/**
	 * Creates the JavaScript-Code which is executed before loading the autosuggest-
	 * data. If a value was set programmatically a single filter for this value is
	 * added to the request to display the label properly. Otherwise the filters
	 * which were defined on the widget are added to the request. The filters are
	 * removed after loading as their values can change because of live-references.
	 *
	 * @return string
	 */
	function build_js_on_beforeload() {
		$widget = $this->get_widget();
		
		// If the value is set data is loaded from the backend. Same if also value-text is set, because otherwise
		// live-references don't work at the beginning. If no value is set, loading from the backend is prevented.
		// The trouble here is, that if the first loading is prevented, the next time the user clicks on the dropdown button,
		// an empty table will be shown, because the last result is cached. To fix this, we bind a reload of the table to
		// onShowPanel in case the grid is empty (see above).
		if (!is_null($this->get_value_with_defaults()) && $this->get_value_with_defaults() !== ''){
			if ($widget->get_value_text()){
				// If the text is already known, set it and prevent initial backend request
				$widget_value_text = str_replace('"', '\"', $widget->get_value_text());
				$first_load_script = <<<JS

						{$this->get_id()}.combogrid("setText", "{$widget_value_text}");
						{$this->get_id()}.data("_lastValidValue", "{$this->get_value_with_defaults()}");
						{$this->get_id()}.data("_currentText", "");
						return false;
JS;
			} else {
				$first_load_script = <<<JS

						{$this->get_id()}.data("_lastValidValue", "{$this->get_value_with_defaults()}");
						{$this->get_id()}.data("_currentText", "");
						{$this->get_id()}.data("_valueSetterUpdate", true);
						currentFilterSet.fltr01_{$widget->get_value_column()->get_data_column_name()} = "{$this->get_value_with_defaults()}";
JS;
			}
		} else {
			// If no value set, just supress initial autoload
			$first_load_script = <<<JS

						{$this->get_id()}.data("_lastValidValue", "");
						{$this->get_id()}.data("_currentText", "");
						return false;
JS;
		}
		
		$fltrId = 0;
		// Add filters from widget
		$filters = [];
		if ($widget->get_table()->has_filters()){
			foreach ($widget->get_table()->get_filters() as $fltr){
				if ($link = $fltr->get_value_widget_link()){
					//filter is a live reference
					$linked_element = $this->get_template()->get_element_by_widget_id($link->get_widget_id(), $this->get_page_id());
					$filters[] = 'currentFilterSet.fltr' . str_pad($fltrId++, 2, 0, STR_PAD_LEFT) . '_' . urlencode($fltr->get_attribute_alias()) . ' = "' . $fltr->get_comparator() . '"+' . $linked_element->build_js_value_getter($link->get_column_id()) . ';';
				} else {
					//filter has a static value
					$filters[] = 'currentFilterSet.fltr' . str_pad($fltrId++, 2, 0, STR_PAD_LEFT) . '_' . urlencode($fltr->get_attribute_alias()) . ' = "' . $fltr->get_comparator() . urlencode(strpos($fltr->get_value(), '=') === 0 ? '' : $fltr->get_value()) . '";';
				}
			}
		}
		$filters_script = implode("\n\t\t\t\t\t\t", $filters);
		// Beim Leeren in einer Gruppe wird kein Filter gesetzt, im Gegensatz zu einem Widget ohne Gruppe.
		$clear_filter_script = $widget->get_lazy_loading_group_id() ? '' : $filters_script;
		// Add value filter (to show proper label for a set value)
		$value_filters = [];
		$value_filters[] = 'currentFilterSet.fltr' . str_pad($fltrId++, 2, 0, STR_PAD_LEFT) . '_' . $widget->get_value_column()->get_data_column_name() . ' = ' . $this->get_id() . '.combogrid("getValues").join();';
		$value_filters_script = implode("\n\t\t\t\t\t\t", $value_filters);
		
		// firstLoadScript:			enthaelt Anweisungen, die nur beim ersten Laden ausgefuehrt
		// 							werden sollen (Initialisierung)
		// filters_script:			enthaelt Anweisungen, welche die gesetzten Filter zur Anfrage
		// 							hinzufuegen
		// value_filters_script:	enthaelt Anweisungen, welche einen Filter zur Anfrage hinzu-
		// 							fuegt, welcher auf dem aktuell gesetzten Wert beruht
		
		// paramGlobal.
		// _firstLoad:				ist nur beim ersten Laden gesetzt
		// _valueSetterUpdate:	ist gesetzt wenn der Wert durch den Value-Setter gesetzt wurde
		// 							und der autosuggest-Inhalt neu geladen werden soll
		// _filterSetterUpdate:	ist gesetzt wenn sich verlinkte Filter geaendert haben und
		// 							der autosuggest-Inhalt neu geladen werden soll
		
		$debug_on_before_load_script = '';
		if ($this->get_js_debug()) {
			$debug_on_before_load_script = <<<JS
					console.debug(Date.now() + ": {$this->get_id()}.onBeforeLoad" + {$this->get_id()}_dataToString());
JS;
		}
		
		$output = <<<JS

					// OnBeforeLoad ist das erste Event, das nach der Erzeugung des Objekts getriggert
					// wird. Daher werden hier globale Variablen initialisiert.
					if (!window.{$this->get_id()}_datagrid) { window.{$this->get_id()}_datagrid = {$this->get_id()}.combogrid("grid"); }
					
					{$debug_on_before_load_script}
					
					// Wird eine Eingabe gemacht, dann aber keine Auswahl getroffen, ist bei der naechsten
					// Anfrage param.q noch gesetzt (param eigentlich nur Kopie???). Deshalb hier loeschen.
					delete param.q;
					
					if (!{$this->get_id()}.data("_lastFilterSet")) { {$this->get_id()}.data("_lastFilterSet", {}); }
					var currentFilterSet = {};
					
					if ({$this->get_id()}.data("_firstLoad") == undefined){
						{$this->get_id()}.data("_firstLoad", true);
					} else if ({$this->get_id()}.data("_firstLoad")){
						{$this->get_id()}.data("_firstLoad", false);
					}
					
					if ({$this->get_id()}.data("_valueSetterUpdate")) {
						{$value_filters_script}
					} else if ({$this->get_id()}.data("_clearFilterSetterUpdate")) {
						{$clear_filter_script}
					} else if ({$this->get_id()}.data("_filterSetterUpdate")) {
						{$filters_script}
						{$value_filters_script}
					} else if ({$this->get_id()}.data("_firstLoad")) {
						{$first_load_script}
					} else {
						if (!param.q) {
							currentFilterSet.q = {$this->get_id()}.combogrid("getText");
						}
						{$filters_script}
					}
					
					// Die Filter der gegenwaertigen Anfrage werden mit den Filtern der letzten Anfrage
					// verglichen. Sind sie identisch und sind noch Daten vorhanden, wird die Anfrage
					// unterbunden, denn das Resultat waere das gleiche.
					if ((JSON.stringify(currentFilterSet) === JSON.stringify({$this->get_id()}.data("_lastFilterSet"))) &&
							({$this->get_id()}_datagrid.datagrid("getRows").length != 0)) {
						return false;
					} else {
						{$this->get_id()}.data("_lastFilterSet", currentFilterSet);
						Object.assign(param, currentFilterSet);
					}
JS;
		
		return $output;
	}
	
	/**
	 * Creates javascript-code which is executed after the successful loading of auto-
	 * suggest-data. All filters are removed as their values can change because of
	 * live-references (filters are added again before the next loading). If
	 * autoselect_single_suggestion is true, a single return value from autosuggest is
	 * automatically selected.
	 * 
	 * @return string
	 */
	function build_js_on_load_sucess() {
		$widget = $this->get_widget();
		
		$uidColumnName = $widget->get_table()->get_uid_column()->get_data_column_name();
		$textColumnName = $widget->get_text_column()->get_data_column_name();
		
		$suppressFilterSetterUpdateScript = $widget->get_lazy_loading_group_id() ? $this->get_id() . '.data("_otherSuppressFilterSetterUpdate", true);' : '';
		$clearFilterSetterUpdateScript = $widget->get_lazy_loading_group_id() ? $this->get_id() . '_clear(true);' : $this->get_id() . '_clear(false);';
		
		$debug_on_load_success_script = $this->get_js_debug() ? 'console.debug(Date.now() + ": ' . $this->get_id() . '.onLoadSuccess");' : '';
		
		$output = <<<JS

					{$debug_on_load_success_script}
					var suppressAutoSelectSingleSuggestion = false;
					
					if ({$this->get_id()}.data("_filterSetterUpdate")) {
						// Nach einem Filter-Setter-Update wird geprueft ob sich die gesetzten Filter und der
						// gesetzte Wert widersprechen.
						var rows = {$this->get_id()}_datagrid.datagrid("getData");
						// Ergibt die Anfrage bei einem FilterSetterUpdate keine Ergebnisse ist wahrscheinlich
						// ein Wert gesetzt, welcher den gesetzten Filtern widerspricht. Deshalb wird der Wert
						// der ComboTable geloescht und anschliessend neu geladen.
						if (rows["total"] == 0) {
							{$this->get_id()}_clear(true);
							{$this->get_id()}_datagrid.datagrid("reload");
						}
						
						{$this->get_id()}.removeData("_filterSetterUpdate");
					}
					if ({$this->get_id()}.data("_clearFilterSetterUpdate")) {
						{$this->get_id()}.removeData("_clearFilterSetterUpdate");
						
						// Ist das Widget in einer filter-group, werden beim leeren keine Referenzen geupdated.
						{$clearFilterSetterUpdateScript}
						
						// Neu geladen werden muss nicht, denn die Filter sind beim vorherigen Laden schon
						// entsprechend gesetzt gewesen.
						
						// Wurde das Widget manuell geloescht, soll nicht wieder automatisch der einzige Suchvorschlag
						// ausgewaehlt werden.
						suppressAutoSelectSingleSuggestion = true;
					}
					if ({$this->get_id()}.data("_valueSetterUpdate")) {
						{$this->get_id()}.removeData("_valueSetterUpdate");
						
						// Nach einem Value-Setter-Update wird der Text neu gesetzt um das Label ordentlich
						// anzuzeigen und das onChange-Skript wird ausgefuehrt.
						var selectedrow = {$this->get_id()}_datagrid.datagrid("getSelected");
						if (selectedrow != null) {
							{$this->get_id()}.combogrid("setText", selectedrow["{$textColumnName}"]);
						}
						
						{$this->get_id()}_onChange();
					}
JS;
		
		if ($widget->get_autoselect_single_suggestion()) {
			$output .= <<<JS

					if (!suppressAutoSelectSingleSuggestion) {
						var rows = {$this->get_id()}_datagrid.datagrid("getData");
						if (rows["total"] == 1) {
							var selectedrow = {$this->get_id()}_datagrid.datagrid("getSelected");
							if (selectedrow == null || selectedrow["{$uidColumnName}"] != rows["rows"][0]["{$uidColumnName}"]) {
								{$suppressFilterSetterUpdateScript}
								// Beim Autoselect wurde ja zuvor schon geladen und es gibt nur noch einen Vorschlag
								// im Resultat (im Gegensatz zur manuellen Auswahl eines Ergebnisses aus einer Liste).
								{$this->get_id()}.data("_suppressReloadOnSelect", true);
								{$this->get_id()}_datagrid.datagrid("selectRow", 0);
								{$this->get_id()}.combogrid("setText", rows["rows"][0]["{$textColumnName}"]);
								{$this->get_id()}.combogrid("hidePanel");
							}
						}
					}
JS;
		}
		
		return $output;
	}
	
	/**
	 * Creates javascript-code which is executed after the erroneous loading of auto-
	 * suggest-data. All filters are removed as their values can change because of
	 * live-references (filters are added again before the next loading).
	 * 
	 * @return string
	 */
	function build_js_on_load_error() {
		$widget = $this->get_widget();
		
		$debug_on_load_error_script = $this->get_js_debug() ? 'console.debug(Date.now() + ": ' . $this->get_id() . '.onLoadError");' : '';
		
		$output = <<<JS

					{$debug_on_load_error_script}
					
					{$this->get_id()}.removeData("_filterSetterUpdate");
					{$this->get_id()}.removeData("_clearFilterSetterUpdate");
					{$this->get_id()}.removeData("_valueSetterUpdate");
JS;
		
		return $output;
	}
	
	/**
	 * 
	 * @return string
	 */
	function build_js_clear_function() {
		$widget = $this->get_widget();
		
		$debug_clear_script = $this->get_js_debug() ? 'console.debug(Date.now() + ": ' . $this->get_id() . '.clear()");' : '';
		
		$output = <<<JS

				function {$this->get_id()}_clear(suppressAllUpdates = false) {
					{$debug_clear_script}
					
					{$this->get_id()}.data("_otherSuppressAllUpdates", suppressAllUpdates);
					{$this->get_id()}.combogrid("clear");
					// Wurde das Widget bereits manuell geleert, wird mit clear kein onChange getriggert und
					// _otherSuppressAllUpdates nicht entfernt. Wird clear mit _otherSuppressAllUpdates
					// gestartet, dann ist _clearFilterSetterUpdate gesetzt. Daher werden hier
					// vorsichtshalber _otherSuppressAllUpdates und _clearFilterSetterUpdate manuell geloescht.
					{$this->get_id()}.removeData("_otherSuppressAllUpdates");
					{$this->get_id()}.removeData("_clearFilterSetterUpdate");
				}
JS;
		return $output;
	}
	
	/**
	 * 
	 * @return boolean
	 */
	function get_js_debug() {
		return $this->js_debug;
	}
	
	/**
	 * 
	 * @param boolean|string $value
	 * @return \exface\JEasyUiTemplate\Template\Elements\euiComboTable
	 */
	function set_js_debug($value) {
		if (is_bool($value)) {
			$this->js_debug = $value;
		} else if (is_string($value)) {
			if (strcasecmp('true', $value) == 0) {
				$this->js_debug = true;
			} else if (strcasecmp('false', $value) == 0) {
				$this->js_debug = false;
			} else {
				$this->js_debug = boolval($value);
			}
		} else {
			throw new InvalidArgumentException('Can not set js_debug for "' . $this->get_id() . '": the argument passed to set_js_debug() is neither a boolean nor a string!');
		}
		return $this;
	}
	
	private function build_js_data_to_string_function() {
		$output = <<<JS

				function {$this->get_id()}_dataToString() {
					var output = 
						"_valueSetterUpdate: " + {$this->get_id()}.data("_valueSetterUpdate") + ", " +
						"_filterSetterUpdate: " + {$this->get_id()}.data("_filterSetterUpdate") + ", " +
						"_clearFilterSetterUpdate: " + {$this->get_id()}.data("_clearFilterSetterUpdate") + ", " +
						"_firstLoad: " + {$this->get_id()}.data("_firstLoad") + ", " +
						"_otherSuppressFilterSetterUpdate: " + {$this->get_id()}.data("_otherSuppressFilterSetterUpdate") + ", " +
						"_otherClearFilterSetterUpdate: " + {$this->get_id()}.data("_otherClearFilterSetterUpdate") + ", " +
						"_otherSuppressAllUpdates: " + {$this->get_id()}.data("_otherSuppressAllUpdates") + ", " +
						"_suppressReloadOnSelect: " + {$this->get_id()}.data("_suppressReloadOnSelect") + ", " +
						"_currentText: " + {$this->get_id()}.data("_currentText") + ", " +
						"_lastValidValue: " + {$this->get_id()}.data("_lastValidValue") + ", " +
						"currentValue: " + {$this->get_id()}.combogrid("getValues").join();
					return output;
				}
JS;
		return $output;
	}
}
?>