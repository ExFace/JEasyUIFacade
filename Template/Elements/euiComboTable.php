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
	
	/**
	 * Folgende privaten Variablen sind im data-Objekt des Elements gespeichert und wichtig
	 * fuer die Funktion desselben:
	 * _valueSetterUpdate				ist gesetzt wenn der Wert des Objekts durch den Value-Setter
	 * 									gesetzt wurde
	 * _filterSetterUpdate				ist gesetzt wenn sich eine Filter-Referenz geaendert hat
	 * _clearFilterSetterUpdate			ist gesetzt wenn das Objekt durch eine Filter-Referenz geleert
	 * 									werden soll
	 * _firstLoad						ist nur beim ersten Laden gesetzt
	 * _otherSuppressFilterSetterUpdate	ist gesetzt wenn die durch eine Filter-Referenz abhaengigen
	 * 									Objekte nicht aktualisiert werden sollen
	 * _otherSupressLazyLoadingGroupUpdate	ist gesetzt wenn die Objekte der gleichen LazyLoadingGroup
	 * 									nicht aktualisiert werden sollen
	 * _otherClearFilterSetterUpdate	ist gesetzt wenn die durch eine Filter-Referenz abhaengigen
	 * 									Objekte geleert werden sollen
	 * _otherSuppressAllUpdates			ist gesetzt wenn alle abhaengigen Objekte (durch Filter- oder
	 * 									Value-Referenz) nicht aktualisiert werden sollen
	 * _suppressReloadOnSelect			ist gesetzt wenn nach dem selektieren eines Eintrags nicht
	 * 									neu geladen werden soll (bei autoselectsinglesuggestion)
	 * _currentText						der seit der letzten gueltigen Auswahl eingegebene Text
	 * _lastValidValue					der letzte gueltige Wert des Objekts
	 * _lastFilterSet					die beim letzten Laden gesetzten Filter
	 * _resultSetChanged				ist gesetzt wenn die geladenen Daten veraendert wurden
	 */
	
	private $js_debug_level = 0;
	
	protected function init(){
		parent::init();
		$this->set_element_type('combogrid');
		$this->set_js_debug_level($this->get_template()->get_config()->get_option("JAVASCRIPT_DEBUG_LEVEL"));
		
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

						if (typeof suppressFilterSetterUpdate == "undefined" || !suppressFilterSetterUpdate) {
							if (typeof clearFilterSetterUpdate == "undefined" || !clearFilterSetterUpdate) {
								$("#{$this->get_id()}").data("_filterSetterUpdate", true);
							} else {
								$("#{$this->get_id()}").data("_clearFilterSetterUpdate", true);
							}
							$("#{$this->get_id()}").combogrid("grid").datagrid("reload");
						}
JS;
					
					if ($widget_lazy_loading_group_id) {
						$on_change_script = <<<JS

					if (typeof suppressLazyLoadingGroupUpdate == "undefined" || !suppressLazyLoadingGroupUpdate) {
						{$on_change_script}
					}
JS;
					}
					
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
		$debug_function = ($this->get_js_debug_level() > 0) ? $this->build_js_debug_data_to_string_function() : '';
		
		$output = <<<JS

			// Globale Variablen initialisieren.
			{$this->build_js_init_globals_function()}
			{$this->get_id()}_initGlobals();
			// Debug-Funktionen hinzufuegen.
			{$debug_function}
			
			{$this->get_id()}_jquery.combogrid({
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
		
		// Das entspricht dem urspruenglichen Verhalten. Filter-Referenzen werden beim Loeschen eines
		// Elements nicht geleert, sondern nur aktualisiert.
		$filter_setter_update_script = $widget->get_lazy_loading_group_id() ? '
								// Der eigene Wert wird geloescht.
								' . $this->get_id() . '_jquery.data("_clearFilterSetterUpdate", true);
								// Loeschen der verlinkten Elemente wenn der Wert manuell geloescht wird.
								' . $this->get_id() . '_jquery.data("_otherClearFilterSetterUpdate", true);'
				: '
								// Loeschen der verlinkten Elemente wenn der Wert manuell geloescht wird.
								// Die Updates der Filter-Links werden an dieser Stelle unterdrueckt und
								// nur einmal nach dem value-Setter update onLoadSuccess ausgefuehrt.
								' . $this->get_id() . '_jquery.data("_suppressFilterSetterUpdate", true);';
		
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
							// Wird getriggert durch manuelle Eingabe oder durch setValue().
							{$this->build_js_debug_message('onChange')}
							// Akualisieren von currentText. Es gibt keine andere gute Moeglichkeit
							// an den gerade eingegebenen Text zu kommen (combogrid("getText") liefert
							// keinen aktuellen Wert). Funktion dieses Wertes siehe onHidePanel.
							{$this->get_id()}_jquery.data("_currentText", newValue);
							if (!newValue) {
								{$this->get_id()}_jquery.data("_lastValidValue", "");
								{$filter_setter_update_script}
								{$this->get_id()}_onChange();
							}
							// Anschließend an onChange wird neu geladen -> onBeforeLoad
						}
						, onSelect: function(index, row) {
							// Wird getriggert durch manuelle Auswahl einer Zeile oder durch
							// setSelection().
							{$this->build_js_debug_message('onSelect')}
							// Aktualisieren von lastValidValue. Loeschen von currentText. Funktion
							// dieser Werte siehe onHidePanel.
							{$this->get_id()}_jquery.data("_lastValidValue", row["{$widget->get_table()->get_uid_column()->get_data_column_name()}"]);
							{$this->get_id()}_jquery.data("_currentText", "");
							
							if ({$this->get_id()}_jquery.data("_suppressReloadOnSelect")) {
								// Verhindert das neu Laden onSelect, siehe onLoadSuccess (autoselectsinglesuggestion)
								{$this->get_id()}_jquery.removeData("_suppressReloadOnSelect");
							} else {
								{$this->get_id()}_jquery.data("_filterSetterUpdate", true);
								{$this->get_id()}_datagrid.datagrid("reload");
							}
							
							//Referenzen werden aktualisiert.
							{$this->get_id()}_onChange();
						}
						, onShowPanel: function() {
							// Wird firstLoad verhindert, wuerde man eine leere Tabelle sehen. Um das zu
							// verhindern wird die Tabelle hier neu geladen, falls sie leer ist.
							// Update: Wird immer noch doppelt geladen, wenn anfangs eine manuelle Eingabe
							// gemacht wird -> onChange (-> Laden), onShowPanel (-> Laden)
							{$this->build_js_debug_message('onShowPanel')}
							if ({$this->get_id()}_jquery.data("_firstLoad")) {
								{$this->get_id()}_datagrid.datagrid("reload");
			                }
						}
						, onHidePanel: function() {
							{$this->build_js_debug_message('onHidePanel')}
							var selectedRow = {$this->get_id()}_datagrid.datagrid("getSelected");
							// lastValidValue enthaelt den letzten validen Wert der ComboTable.
							var lastValidValue = {$this->get_id()}_jquery.data("_lastValidValue");
							var currentValue = {$this->get_id()}_jquery.combogrid("getValues").join();
							// currentText enthaelt den seit der letzten validen Auswahl in die ComboTable eingegebenen Text,
							// d.h. ist currentText nicht leer wurde Text eingegeben aber noch keine Auswahl getroffen.
							var currentText = {$this->get_id()}_jquery.data("_currentText");
							
							// Das Panel wird automatisch versteckt, wenn man das Eingabefeld verlaesst.
							// Wurde zu diesem Zeitpunkt seit der letzten Auswahl Text eingegeben, aber
							// kein Eintrag ausgewaehlt, dann wird der letzte valide Zustand wiederher-
							// gestellt.
							if (selectedRow == null && currentText) {
								if (lastValidValue){
									{$this->get_id()}_jquery.data("_currentText", "");
									{$this->get_id()}_valueSetter(lastValidValue);
								} else {
									{$this->get_id()}_jquery.data("_currentText", "");
									{$this->get_id()}_clear(true);
									if (currentValue != lastValidValue) {
										{$this->get_id()}_datagrid.datagrid("reload");
									}
								}
							}
						}
						, onDestroy: function() {
							// Wird leider nicht getriggert, sonst waere das eine gute Moeglichkeit
							// die globalen Variablen nur nach Bedarf zu initialisieren.
							{$this->build_js_debug_message('onDestroy')}
							
							delete {$this->get_id()}_jquery;
							delete {$this->get_id()}_datagrid;
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
						return {$this->get_id()}_jquery.combogrid("getValues").join();
JS;
		} else {
			$uidColumnName = $widget->get_table()->get_uid_column()->get_data_column_name();
			
			$value_getter = <<<JS
						if (column){
							var row = {$this->get_id()}_datagrid.datagrid("getSelected");
							if (row) {
								if (row[column] == undefined) {
									if (window.console) { console.warn("The non-existing column \"" + column + "\" was requested from element \"{$this->get_id()}\""); }
								}
								return row[column];
							} else if (column == "{$uidColumnName}") {
								// Wurde durch den prefill nur value und text gesetzt, aber noch
								// nichts geladen (daher auch keine Auswahl) dann wird der gesetzte
								// value zurueckgegeben wenn die OID-Spalte angefragt wird (wichtig
								// fuer das Funktionieren von Filtern bei initialem Laden).
								return {$this->get_id()}_jquery.combogrid("getValues").join();
							} else {
								return "";
							}
						} else {
							return {$this->get_id()}_jquery.combogrid("getValues").join();
						}
JS;
		}
		
		$output = <<<JS
				
				function {$this->get_id()}_valueGetter(column, row){
					// Der value-Getter wird in manchen Faellen aufgerufen, bevor die globalen
					// Variablen definiert sind. Daher hier noch einmal initialisieren.
					{$this->get_id()}_initGlobals();
					
					{$this->build_js_debug_message('valueGetter()')}
					
					if ({$this->get_id()}_jquery.data("combogrid")) {
						{$value_getter}
					} else {
						return {$this->get_id()}_jquery.val();
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
							{$this->get_id()}_jquery.combogrid("setValues", valueArray);
JS;
		} else {
			$value_setter = <<<JS
							if (valueArray.length <= 1) {
								{$this->get_id()}_jquery.combogrid("setValues", valueArray);
							}
JS;
		}
								
		$output = <<<JS
				
				function {$this->get_id()}_valueSetter(value){
					{$this->build_js_debug_message('valueSetter()')}
					var valueArray;
					if ({$this->get_id()}_jquery.data("combogrid")) {
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
						if (!{$this->get_id()}_jquery.combogrid("getValues").equals(valueArray)) {
							//onChange wird getriggert
							{$value_setter}
							
							{$this->get_id()}_jquery.data("_lastValidValue", valueArray.join());
							{$this->get_id()}_jquery.data("_valueSetterUpdate", true);
							{$this->get_id()}_datagrid.datagrid("reload");
						}
					} else {
						{$this->get_id()}_jquery.val(value).trigger("change");
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
		
		$output = <<<JS
				
				function {$this->get_id()}_onChange(){
					{$this->build_js_debug_message('onChange()')}
					// Diese Werte koennen gesetzt werden damit, wenn der Wert der ComboTable
					// geaendert wird, nur ein Teil oder gar keine verlinkten Elemente aktualisiert
					// werden.
					var suppressFilterSetterUpdate = false, clearFilterSetterUpdate = false, suppressAllUpdates = false, suppressLazyLoadingGroupUpdate = false;
					if ({$this->get_id()}_jquery.data("_otherSuppressFilterSetterUpdate")){
						// Es werden keine Filter-Links aktualisiert.
						{$this->get_id()}_jquery.removeData("_otherSuppressFilterSetterUpdate");
						suppressFilterSetterUpdate = true;
					}
					if ({$this->get_id()}_jquery.data("_otherClearFilterSetterUpdate")){
						// Filter-Links werden geleert.
						{$this->get_id()}_jquery.removeData("_otherClearFilterSetterUpdate");
						clearFilterSetterUpdate = true;
					}
					if ({$this->get_id()}_jquery.data("_otherSuppressAllUpdates")){
						// Weder Werte-Links noch Filter-Links werden aktualisiert.
						{$this->get_id()}_jquery.removeData("_otherSuppressAllUpdates");
						suppressAllUpdates = true;
					}
					if ({$this->get_id()}_jquery.data("_otherSuppressLazyLoadingGroupUpdate")){
						// Die LazyLoadingGroup wird nicht aktualisiert.
						{$this->get_id()}_jquery.removeData("_otherSuppressLazyLoadingGroupUpdate");
						suppressLazyLoadingGroupUpdate = true;
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

						{$this->get_id()}_jquery.combogrid("setText", "{$widget_value_text}");
						{$this->get_id()}_jquery.data("_lastValidValue", "{$this->get_value_with_defaults()}");
						{$this->get_id()}_jquery.data("_currentText", "");
						return false;
JS;
			} else {
				$first_load_script = <<<JS

						{$this->get_id()}_jquery.data("_lastValidValue", "{$this->get_value_with_defaults()}");
						{$this->get_id()}_jquery.data("_currentText", "");
						{$this->get_id()}_jquery.data("_valueSetterUpdate", true);
						currentFilterSet.fltr01_{$widget->get_value_column()->get_data_column_name()} = "{$this->get_value_with_defaults()}";
JS;
			}
		} else {
			// If no value set, just supress initial autoload
			$first_load_script = <<<JS

						{$this->get_id()}_jquery.data("_lastValidValue", "");
						{$this->get_id()}_jquery.data("_currentText", "");
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
		// Beim Leeren eines Widgets in einer in einer lazy-loading-group wird kein Filter gesetzt,
		// denn alle Filter sollten leer sein (alle Elemente der Gruppe leer). Beim Leeren eines
		// Widgets ohne Gruppe werden die normalen Filter gesetzt.
		$clear_filters_script = $widget->get_lazy_loading_group_id() ? '' : $filters_script;
		// Add value filter (to show proper label for a set value)
		$value_filters = [];
		$value_filters[] = 'currentFilterSet.fltr' . str_pad($fltrId++, 2, 0, STR_PAD_LEFT) . '_' . $widget->get_value_column()->get_data_column_name() . ' = ' . $this->get_id() . '_jquery.combogrid("getValues").join();';
		$value_filters_script = implode("\n\t\t\t\t\t\t", $value_filters);
		
		// firstLoadScript:			enthaelt Anweisungen, die nur beim ersten Laden ausgefuehrt
		// 							werden sollen (Initialisierung)
		// filters_script:			fuegt die gesetzten Filter zur Anfrage hinzu
		// value_filters_script:	fuegt einen Filter zur Anfrage hinzu, welcher auf dem
		// 							aktuell gesetzten Wert beruht
		// clear_filters_script:	fuegt Filter zur Anfrage hinzu, welche beim Leeren des
		// 							Objekts gelten sollen
		
		$output = <<<JS

					// OnBeforeLoad ist das erste Event, das nach der Erzeugung des Objekts getriggert
					// wird. Daher werden hier globale Variablen initialisiert (_datagrid kann vorher
					// nicht initialisiert werden, da das combogrid-Objekt noch nicht existiert).
					{$this->get_id()}_initGlobals();
					
					{$this->build_js_debug_message('onBeforeLoad')}
					
					// Wird eine Eingabe gemacht, dann aber keine Auswahl getroffen, ist bei der naechsten
					// Anfrage param.q noch gesetzt (param eigentlich nur Kopie???). Deshalb hier loeschen.
					delete param.q;
					
					if (!{$this->get_id()}_jquery.data("_lastFilterSet")) { {$this->get_id()}_jquery.data("_lastFilterSet", {}); }
					var currentFilterSet = {};
					
					if ({$this->get_id()}_jquery.data("_firstLoad") == undefined){
						{$this->get_id()}_jquery.data("_firstLoad", true);
					} else if ({$this->get_id()}_jquery.data("_firstLoad")){
						{$this->get_id()}_jquery.data("_firstLoad", false);
					}
					
					if ({$this->get_id()}_jquery.data("_valueSetterUpdate")) {
						param._valueSetterUpdate = true;
						{$value_filters_script}
					} else if ({$this->get_id()}_jquery.data("_clearFilterSetterUpdate")) {
						param._clearFilterSetterUpdate = true;
						{$clear_filters_script}
					} else if ({$this->get_id()}_jquery.data("_filterSetterUpdate")) {
						param._filterSetterUpdate = true;
						{$filters_script}
						{$value_filters_script}
					} else if ({$this->get_id()}_jquery.data("_firstLoad")) {
						param._firstLoad = true;
						{$first_load_script}
					} else {
						if (!param.q) {
							currentFilterSet.q = {$this->get_id()}_jquery.combogrid("getText");
						}
						{$filters_script}
					}
					
					// Die Filter der gegenwaertigen Anfrage werden mit den Filtern der letzten Anfrage
					// verglichen. Sind sie identisch und wurden die zuletzt geladenen Daten nicht ver-
					// aendert, wird die Anfrage unterbunden, denn das Resultat waere das gleiche.
					if ((JSON.stringify(currentFilterSet) === JSON.stringify({$this->get_id()}_jquery.data("_lastFilterSet"))) &&
							!({$this->get_id()}_jquery.data("_resultSetChanged"))) {
						// Suchart entfernen, sonst ist sie beim naechsten Mal noch gesetzt
						{$this->get_id()}_jquery.removeData("_valueSetterUpdate");
						{$this->get_id()}_jquery.removeData("_clearFilterSetterUpdate");
						{$this->get_id()}_jquery.removeData("_filterSetterUpdate");
						
						return false;
					} else {
						{$this->get_id()}_jquery.data("_lastFilterSet", currentFilterSet);
						Object.assign(param, currentFilterSet);
					}
JS;
		/* FIXME how to make multiselects search for every text in the list separately. The trouble is, 
		 * the combotable seems to drop _all_ it's values once you continue typing. It will only restore
		 * them if the returned resultset contains them too. 
		if ($widget->get_multi_select()){
			$output .= '
					if (param.q.indexOf("' . $widget->get_multi_select_text_delimiter() . '") !== -1){	
						// The idea here was to send a list of texts for an IN-query. This returns no results though, as
						// the SQL "IN" expects exact matches, no LIKEs
						//param.q = "["+param.q;
						
						// Here the q-parameter was to be split into "old" and new part and the search would only be done with the
						// new part. This did not work because the old values would get lost and be replaced by the text. To cope
						// with this the ID filter was to be used, but it would add an AND to the query, not an OR.
						//param.q = param.q.substring(param.q.lastIndexOf("' . $widget->get_multi_select_text_delimiter() . '") + 1);
						//param.fltr01_' . $widget->get_value_column()->get_data_column_name() . ' = $("#' . $this->get_id() .'").data("lastValidValue");
					}
			';
		}*/
		
		return $output;
	}
	
	/**
	 * Creates javascript-code which is executed after the successful loading of auto-
	 * suggest-data. If autoselect_single_suggestion is true, a single return value
	 * from autosuggest is automatically selected.
	 * 
	 * @return string
	 */
	function build_js_on_load_sucess() {
		$widget = $this->get_widget();
		
		$uidColumnName = $widget->get_table()->get_uid_column()->get_data_column_name();
		$textColumnName = $widget->get_text_column()->get_data_column_name();
		
		$suppressLazyLoadingGroupUpdateScript = $widget->get_lazy_loading_group_id() ? $this->get_id() . '_jquery.data("_otherSuppressLazyLoadingGroupUpdate", true);' : '';
		
		$output = <<<JS

					{$this->build_js_debug_message('onLoadSuccess')}
					var suppressAutoSelectSingleSuggestion = false;
					
					if ({$this->get_id()}_jquery.data("_valueSetterUpdate")) {
						// Update durch eine value-Referenz.
						
						{$this->get_id()}_jquery.removeData("_valueSetterUpdate");
						{$this->get_id()}_jquery.removeData("_clearFilterSetterUpdate");
						{$this->get_id()}_jquery.removeData("_filterSetterUpdate");
						
						// Nach einem Value-Setter-Update wird der Text neu gesetzt um das Label ordentlich
						// anzuzeigen und das onChange-Skript wird ausgefuehrt.
						var selectedrow = {$this->get_id()}_datagrid.datagrid("getSelected");
						if (selectedrow != null) {
							{$this->get_id()}_jquery.combogrid("setText", selectedrow["{$textColumnName}"]);
						}
						
						{$this->get_id()}_onChange();
					} else if ({$this->get_id()}_jquery.data("_clearFilterSetterUpdate")) {
						// Leeren durch eine filter-Referenz.
						
						{$this->get_id()}_jquery.removeData("_valueSetterUpdate");
						{$this->get_id()}_jquery.removeData("_clearFilterSetterUpdate");
						{$this->get_id()}_jquery.removeData("_filterSetterUpdate");
						
						{$this->get_id()}_clear(false);
						
						// Neu geladen werden muss nicht, denn die Filter waren beim vorangegangenen Laden schon
						// entsprechend gesetzt.
						
						// Wurde das Widget manuell geloescht, soll nicht wieder automatisch der einzige Suchvorschlag
						// ausgewaehlt werden.
						suppressAutoSelectSingleSuggestion = true;
					} else if ({$this->get_id()}_jquery.data("_filterSetterUpdate")) {
						// Update durch eine filter-Referenz.
						
						// Ergibt die Anfrage bei einem FilterSetterUpdate keine Ergebnisse ist wahrscheinlich
						// ein Wert gesetzt, welcher den gesetzten Filtern widerspricht. Deshalb wird der Wert
						// der ComboTable geloescht und anschliessend neu geladen.
						var rows = {$this->get_id()}_datagrid.datagrid("getData");
						if (rows["total"] == 0) {
							{$this->get_id()}_clear(true);
							{$this->get_id()}_datagrid.datagrid("reload");
						}
						
						{$this->get_id()}_jquery.removeData("_valueSetterUpdate");
						{$this->get_id()}_jquery.removeData("_clearFilterSetterUpdate");
						{$this->get_id()}_jquery.removeData("_filterSetterUpdate");
					}
					
					// Das resultSet wurde neu geladen und ist daher unveraendert. Ein erneutes Laden mit
					// identischem filterSet kann unterbunden werden (siehe onBeforeLoad).
					{$this->get_id()}_jquery.data("_resultSetChanged", false);
JS;
		
		if ($widget->get_autoselect_single_suggestion()) {
			$output .= <<<JS

					if (!suppressAutoSelectSingleSuggestion) {
						// Automatisches Auswaehlen des einzigen Suchvorschlags.
						var rows = {$this->get_id()}_datagrid.datagrid("getData");
						if (rows["total"] == 1) {
							var selectedrow = {$this->get_id()}_datagrid.datagrid("getSelected");
							if (selectedrow == null || selectedrow["{$uidColumnName}"] != rows["rows"][0]["{$uidColumnName}"]) {
								// Ist das Widget in einer lazy-loading-group, werden keine Filter-Referenzen aktualisiert,
								// denn alle Elemente der Gruppe werden vom Orginalobjekt bedient.
								{$suppressLazyLoadingGroupUpdateScript}
								// Beim Autoselect wurde ja zuvor schon geladen und es gibt nur noch einen Vorschlag
								// im Resultat (im Gegensatz zur manuellen Auswahl eines Ergebnisses aus einer Liste).
								{$this->get_id()}_jquery.data("_suppressReloadOnSelect", true);
								// onSelect wird getriggert
								{$this->get_id()}_datagrid.datagrid("selectRow", 0);
								{$this->get_id()}_jquery.combogrid("setText", rows["rows"][0]["{$textColumnName}"]);
								{$this->get_id()}_jquery.combogrid("hidePanel");
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
	function build_js_on_load_error() {
		$widget = $this->get_widget();
		
		$output = <<<JS

					{$this->build_js_debug_message('onLoadError')}
					
					{$this->get_id()}_jquery.removeData("_valueSetterUpdate");
					{$this->get_id()}_jquery.removeData("_clearFilterSetterUpdate");
					{$this->get_id()}_jquery.removeData("_filterSetterUpdate");
JS;
		
		return $output;
	}
	
	/**
	 * Creates a javascript-function which empties the object. If the object had a value
	 * before, onChange is triggered by clearing it. If suppressAllUpdates = true is
	 * passed to the function, linked elements are not updated by clearing the object.
	 * This behavior is usefull, if the object should really just be cleared.
	 * 
	 * @return string
	 */
	function build_js_clear_function() {
		$widget = $this->get_widget();
		
		$output = <<<JS

				function {$this->get_id()}_clear(suppressAllUpdates) {
					{$this->build_js_debug_message('clear()')}
					
					// Bestimmt ob durch das Leeren andere verlinkte Elemente aktualisiert werden sollen. 
					{$this->get_id()}_jquery.data("_otherSuppressAllUpdates", suppressAllUpdates);
					// Beim Leeren wird die LazyLoadingGroup (wenn es eine gibt) nicht aktualisiert.
					{$this->get_id()}_jquery.data("_otherSuppressLazyLoadingGroupUpdate", true);
					// Durch das Leeren aendert sich das resultSet und es sollte das naechste Mal neu geladen
					// werden, auch wenn sich das Filterset nicht geaendert hat (siehe onBeforeLoad).
					{$this->get_id()}_jquery.data("_resultSetChanged", true);
					// Triggert onChange, wenn vorher ein Element ausgewaehlt war.
					{$this->get_id()}_jquery.combogrid("clear");
					// Wurde das Widget bereits manuell geleert, wird mit clear kein onChange getriggert und
					// _otherSuppressAllUpdates nicht entfernt. Wird clear mit _otherSuppressAllUpdates
					// gestartet, dann ist hinterher _clearFilterSetterUpdate gesetzt. Daher werden hier
					// vorsichtshalber _otherSuppressAllUpdates und _clearFilterSetterUpdate manuell geloescht.
					{$this->get_id()}_jquery.removeData("_otherSuppressAllUpdates");
					{$this->get_id()}_jquery.removeData("_otherSuppressLazyLoadingGroupUpdate");
					{$this->get_id()}_jquery.removeData("_clearFilterSetterUpdate");
				}
JS;
		return $output;
	}
	
	function get_js_debug_level() {
		return $this->js_debug_level;
	}
	
	/**
	 * Determines the detail-level of the debug-messages which are written to the browser-
	 * console.
	 * 0 = off, 1 = low, 2 = medium, 3 = high detail-level (default: 0)
	 * 
	 * @param integer|string $value
	 * @return \exface\JEasyUiTemplate\Template\Elements\euiComboTable
	 */
	function set_js_debug_level($value) {
		if (is_int($value)) {
			$this->js_debug_level = $value;
		} else if (is_string($value)) {
			$this->js_debug_level = intval($value);
		} else {
			throw new InvalidArgumentException('Can not set js_debug_level for "' . $this->get_id() . '": the argument passed to set_js_debug_level() is neither an integer nor a string!');
		}
		return $this;
	}
	
	/**
	 * Creates javascript-code that writes a debug-message to the browser-console.
	 * 
	 * @param string $source
	 * @return string
	 */
	function build_js_debug_message($source) {
		switch ($this->get_js_debug_level()) {
			case 0:
				$output = '';
				break;
			case 1:
			case 2:
				$output = <<<JS
				if (window.console) { console.debug(Date.now() + "|{$this->get_id()}.{$source}"); }
JS;
				break;
			case 3:
				$output = <<<JS
				if (window.console) { console.debug(Date.now() + "|{$this->get_id()}.{$source}|" + {$this->get_id()}_debugDataToString()); }
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
	 * are important for the function of the object. It is required for debug-messages with
	 * a high detail-level. 
	 * 
	 * @return string
	 */
	function build_js_debug_data_to_string_function() {
		$output = <<<JS
		
				function {$this->get_id()}_debugDataToString() {
					var currentValue = {$this->get_id()}_jquery.data("combogrid") ? {$this->get_id()}_jquery.combogrid("getValues").join() : {$this->get_id()}_jquery.val();;
					var output =
						"_valueSetterUpdate: " + {$this->get_id()}_jquery.data("_valueSetterUpdate") + ", " +
						"_filterSetterUpdate: " + {$this->get_id()}_jquery.data("_filterSetterUpdate") + ", " +
						"_clearFilterSetterUpdate: " + {$this->get_id()}_jquery.data("_clearFilterSetterUpdate") + ", " +
						"_firstLoad: " + {$this->get_id()}_jquery.data("_firstLoad") + ", " +
						"_otherSuppressFilterSetterUpdate: " + {$this->get_id()}_jquery.data("_otherSuppressFilterSetterUpdate") + ", " +
						"_otherClearFilterSetterUpdate: " + {$this->get_id()}_jquery.data("_otherClearFilterSetterUpdate") + ", " +
						"_otherSuppressAllUpdates: " + {$this->get_id()}_jquery.data("_otherSuppressAllUpdates") + ", " +
						"_otherSuppressLazyLoadingGroupUpdate: " + {$this->get_id()}_jquery.data("_otherSuppressLazyLoadingGroupUpdate") + ", " +
						"_suppressReloadOnSelect: " + {$this->get_id()}_jquery.data("_suppressReloadOnSelect") + ", " +
						"_currentText: " + {$this->get_id()}_jquery.data("_currentText") + ", " +
						"_lastValidValue: " + {$this->get_id()}_jquery.data("_lastValidValue") + ", " +
						"currentValue: " + currentValue + ", " +
						"_lastFilterSet: "+ JSON.stringify({$this->get_id()}_jquery.data("_lastFilterSet")) + ", " +
						"_resultSetChanged: " + {$this->get_id()}_jquery.data("_resultSetChanged");
					return output;
				}
JS;
		return $output;
	}
	
	function build_js_init_globals_function() {
		$output = <<<JS

				function {$this->get_id()}_initGlobals() {
					window.{$this->get_id()}_jquery = $("#{$this->get_id()}");
					if ({$this->get_id()}_jquery.data("combogrid")) {
						window.{$this->get_id()}_datagrid = {$this->get_id()}_jquery.combogrid("grid");
					}
				}
JS;
		return $output;
	}
}
?>