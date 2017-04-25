<?php
namespace exface\JEasyUiTemplate\Template\Elements;

use exface\Core\Widgets\ComboTable;

/**
 * 
 * @method ComboTable get_widget()
 * 
 * @author Andrej Kabachnik
 *
 */
class euiComboTable extends euiInput {
	
	protected function init(){
		parent::init();
		$this->set_element_type('combogrid');
		
		// Register onChange-Handler for Filters with Live-Reference-Values
		$widget = $this->get_widget();
		if ($widget->get_table()->has_filters()){
			foreach ($widget->get_table()->get_filters() as $fltr){
				if ($fltr->get_value_expression() && $fltr->get_value_expression()->is_reference()){
					$link = $fltr->get_value_expression()->get_widget_link();
					$linked_element = $this->get_template()->get_element_by_widget_id($link->get_widget_id(), $this->get_page_id());
					$linked_element->add_on_change_script('
							// Ist suppressFilterSetterUpdate == true wird nicht neu geladen. Dadurch
							// wird unnoetiges neu Laden verhindert (siehe onChange).
							if (typeof suppressFilterSetterUpdate == "undefined" || !suppressFilterSetterUpdate) {
								$("#' . $this->get_id() . '").combogrid("grid").datagrid("options").queryParams._jsFilterSetterUpdate = true;
								$("#' . $this->get_id() . '").combogrid("grid").datagrid("reload");
							}');
				}
			}
		}
	}
	
	function generate_html(){
		/* @var $widget \exface\Core\Widgets\ComboTable */
		$widget = $this->get_widget();
		$value = $this->get_value_with_defaults();
		$output = '
				<input style="height:100%;width:100%;"
					id="' . $this->get_id() . '" 
					name="' . $widget->get_attribute_alias() . ($widget->get_multi_select() ? '[]' : '') . '" 
					value="' . $value . '"
					' . ($widget->is_required() ? 'required="true" ' : '') . '
					' . ($widget->is_disabled() ? 'disabled="disabled" ' : '') . ' />';
		
		return $this->build_html_wrapper_div($output);
	}
	
	function generate_js(){
		$output .= '$("#' . $this->get_id() . '").combogrid({';
		$output .= $this->build_js_init_options();
		$output .= '});';
		
		// Es werden JavaScript Value-Getter-/Setter- und OnChange-Funktionen fuer die ComboTable erzeugt,
		// um duplizierten Code zu vermeiden.
		$output .= '
				' . $this->build_js_value_getter_function() . '
				' . $this->build_js_value_setter_function() . '
				' . $this->build_js_on_change_function();
		
		// Es werden Dummy-Methoden fuer die Filter der DataTable hinter dieser ComboTable generiert. Diese
		// Funktionen werden nicht benoetigt, werden aber trotzdem vom verlinkten Element aufgerufen, da
		// dieses nicht entscheiden kann, ob das Filter-Input-Widget existiert oder nicht. Fuer diese Filter
		// existiert kein Input-Widget, daher existiert fuer sie weder HTML- noch JavaScript-Code und es
		// kommt sonst bei einem Aufruf der Funktion zu einem Fehler. 
		if ($this->get_widget()->get_table()->has_filters()) {
			foreach ($this->get_widget()->get_table()->get_filters() as $fltr) {
				$output .= '
				function ' . $this->get_template()->get_element($fltr->get_widget())->get_id() . '_valueSetter(value){}';
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
		$table->set_on_before_load($this->build_js_on_beforeload_live_reference());
		$table->add_on_load_success($this->build_js_on_load_sucess_live_reference());
		$table->add_on_load_error($this->build_js_on_load_error_live_reference());
		
		$table->add_on_load_success($this->build_js_on_load_success_autoselect());
		$inherited_options .= $table->build_js_init_options_head();
		
		$inherited_options = trim($inherited_options, "\r\n\t,");
		$output .= $inherited_options . '
						, textField:"' . $this->get_widget()->get_text_column()->get_data_column_name() . '"
						, mode: "remote"
						, method: "post"
						, delay: 600
						, panelWidth:600
						' . ($widget->is_required() ? ', required:true' : '') . '
						' . ($widget->is_disabled() ? ', disabled:true' : '') . '
						' . ($widget->get_multi_select() ? ', multiple: true' : '') . '
						, onChange: function(newValue, oldValue) {
							var ' . $this->get_id() . '_cg = $("#' . $this->get_id() . '");
							// Akualisieren von currentText. Es gibt keine andere gute Moeglichkeit
							// an den gerade eingegebenen Text zu kommen (combogrid("getText") liefert
							// keinen aktuellen Wert). Funktion dieses Wertes siehe onHidePanel.
							' . $this->get_id() . '_cg.data("currentText", newValue);
							if (!newValue) {
								' . $this->get_id() . '_cg.data("lastValidValue", "");
								// Loeschen der verlinkten Elemente wenn der Wert manuell geloescht wird.
								// Die Updates der Filter-Links werden an dieser Stelle unterdrueckt und
								// nur einmal nach dem value-Setter update onLoadSuccess ausgefuehrt.
								' . $this->get_id() . '_cg.combogrid("grid").datagrid("options").queryParams.suppressFilterSetterUpdate = true;
								' . $this->get_id() . '_onChange();
							}
						}
						, onSelect: function(index, row) {
							var ' . $this->get_id() . '_cg = $("#' . $this->get_id() . '");
							// Aktualisieren von lastValidValue. Loeschen von currentText. Funktion
							// dieser Werte siehe onHidePanel.
							' . $this->get_id() . '_cg.data("lastValidValue", row["' . $widget->get_table()->get_uid_column()->get_data_column_name() . '"]);
							' . $this->get_id() . '_cg.data("currentText", "");
							' . $this->get_id() . '_onChange();
						}
						, onShowPanel: function() {
							// Wird firstLoad verhindert, wuerde man eine leere Tabelle sehen. Um das zu
							// verhindern wird die Tabelle hier neu geladen, falls sie leer ist.
							// Update: dadurch wird bei anfaenglicher manueller Eingabe eines Wertes doppelt geladen
							if($(this).combogrid("grid").datagrid("getRows").length == 0) {
								$(this).combogrid("grid").datagrid("reload");
			                }
						}
						, onHidePanel: function() {
							var ' . $this->get_id() . '_cg = $("#' . $this->get_id() . '");
							var selectedRow = ' . $this->get_id() . '_cg.combogrid("grid").datagrid("getSelected");
							// lastValidValue enthaelt den letzten validen Wert der ComboTable.
							var lastValidValue = ' . $this->get_id() . '_cg.data("lastValidValue");
							var currentValue = ' . $this->get_id() . '_cg.combogrid("getValues").join();
							// currentText enthaelt den seit der letzten validen Auswahl in die ComboTable eingegebenen Text,
							// d.h. ist currentText nicht leer wurde Text eingegeben aber noch keine Auswahl getroffen.
							var currentText =' . $this->get_id() . '_cg.data("currentText");
							
							// Das Panel wird automatisch versteckt, wenn man das Eingabefeld verlaesst.
							// Wurde zu diesem Zeitpunkt seit der letzten Auswahl Text eingegeben, aber
							// kein Eintrag ausgewaehlt, dann wird der letzte valide Zustand wiederher-
							// gestellt.
							if (selectedRow == null && currentText) {
								if (lastValidValue){
									' . $this->get_id() . '_cg.data("currentText", "");
									' . $this->get_id() . '_valueSetter(lastValidValue);
								} else {
									' . $this->get_id() . '_cg.data("currentText", "");
									' . $this->get_id() . '_cg.combogrid("setText", "");
									if (currentValue != lastValidValue) {
										' . $this->get_id() . '_cg.combogrid("grid").datagrid("reload");
									}
								}
							}
						}';
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
		if ($this->get_widget()->get_multi_select()){
			$value_getter = <<<JS
						return {$this->get_id()}_cg.combogrid("getValues").join();
JS;
		} else {
			$value_getter = <<<JS
						if (column){
							var row = {$this->get_id()}_cg.combogrid("grid").datagrid("getSelected");
							if (row) {
								if (row[column] == undefined){
									{$this->get_id()}_cg.combogrid("grid").datagrid("reload");
								}
								return row[column];
							} else { 
								return "";
							}
						} else {
							return {$this->get_id()}_cg.combogrid("getValues").join();
						}
JS;
		}
		
		$output = <<<JS
				
				function {$this->get_id()}_valueGetter(column, row){
					var {$this->get_id()}_cg = $("#{$this->get_id()}");
					if ({$this->get_id()}_cg.data("combogrid")) {
						{$value_getter}
					} else {
						return {$this->get_id()}_cg.val();
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
		if ($this->get_widget()->get_multi_select()) {
			$value_setter = <<<JS
							{$this->get_id()}_cg.combogrid("setValues", valueArray);
JS;
		} else {
			$value_setter = <<<JS
							if (valueArray.length <= 1) {
								{$this->get_id()}_cg.combogrid("setValues", valueArray);
							}
JS;
		}
		
		$output = <<<JS
				
				function {$this->get_id()}_valueSetter(value){
					var {$this->get_id()}_cg = $("#{$this->get_id()}");
					var valueArray;
					if ({$this->get_id()}_cg.data("combogrid")) {
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
						if (!{$this->get_id()}_cg.combogrid("getValues").equals(valueArray)) {
							{$value_setter}
							
							{$this->get_id()}_cg.data("lastValidValue", valueArray.join());
							
							{$this->get_id()}_cg.combogrid("grid").datagrid("options").queryParams._jsValueSetterUpdate = true;
							{$this->get_id()}_cg.combogrid("grid").datagrid("reload");
						}
					} else {
						{$this->get_id()}_cg.val(value).trigger("change");
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
		$output = <<<JS
				
				function {$this->get_id()}_onChange(){
					var {$this->get_id()}_cg = $("#{$this->get_id()}");
					var dataUrlParams = {$this->get_id()}_cg.combogrid("grid").datagrid("options").queryParams;
					// Diese Werte koennen gesetzt werden damit, wenn der Wert der ComboTable
					// geaendert wird, nur ein Teil oder gar keine verlinkten Elemente geupdated
					// werden.
					var suppressFilterSetterUpdate, suppressAllUpdates;
					if (dataUrlParams.suppressFilterSetterUpdate != undefined){
						if (dataUrlParams.suppressFilterSetterUpdate){
							// Es werden keine Filter-Links aktualisiert.
							suppressFilterSetterUpdate = true;
						} else {
							suppressFilterSetterUpdate = false;
						}
						delete dataUrlParams.suppressFilterSetterUpdate;
					}
					if (dataUrlParams.suppressAllUpdates != undefined){
						if (dataUrlParams.suppressAllUpdates){
							// Weder Werte-Links noch Filter-Links werden aktualisiert.
							suppressAllUpdates = true;
						} else {
							suppressAllUpdates = false;
						}
						delete dataUrlParams.suppressAllUpdates;
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
	function build_js_on_beforeload_live_reference() {
		$widget = $this->get_widget();
		
		// If the value is set data is loaded from the backend. Same if also value-text is set, because otherwise
		// live-references don't work at the beginning. If no value is set, loading from the backend is prevented.
		// The trouble here is, that if the first loading is prevented, the next time the user clicks on the dropdown button,
		// an empty table will be shown, because the last result is cached. To fix this, we bind a reload of the table to
		// onShowPanel in case the grid is empty (see above).
		if (!is_null($this->get_value_with_defaults()) && $this->get_value_with_defaults() !== ''){
			if ($widget->get_value_text()){
				// If the text is already known, set it and prevent initial backend request
				$first_load_script = '
						$("#' . $this->get_id() .'").' . $this->get_element_type() . '("setText", "' . str_replace('"', '\"', $widget->get_value_text()) . '");
						$("#' . $this->get_id() .'").data("lastValidValue", "' . $this->get_value_with_defaults() . '");
						$("#' . $this->get_id() .'").data("currentText", "");
						return false;';
			} else {
				$first_load_script = '
						$("#' . $this->get_id() .'").data("lastValidValue", "' . $this->get_value_with_defaults() . '");
						$("#' . $this->get_id() .'").data("currentText", "");
						paramGlobal._jsValueSetterUpdate = true;
						param.fltr01_' . $widget->get_value_column()->get_data_column_name() . ' = "' . $this->get_value_with_defaults() . '";';
			}
		} else {
			// If no value set, just supress initial autoload
			$first_load_script = '
						$("#' . $this->get_id() .'").data("lastValidValue", "");
						$("#' . $this->get_id() .'").data("currentText", "");
						return false;';
		}
		
		$fltrId = 0;
		// Add filters from widget
		$filters = [];
		if ($widget->get_table()->has_filters()){
			foreach ($widget->get_table()->get_filters() as $fltr){
				if ($fltr->get_value_expression() && $fltr->get_value_expression()->is_reference()){
					//filter is a live reference
					$link = $fltr->get_value_expression()->get_widget_link();
					$linked_element = $this->get_template()->get_element_by_widget_id($link->get_widget_id(), $this->get_page_id());
					$filters[] = 'param.fltr' . str_pad($fltrId++, 2, 0, STR_PAD_LEFT) . '_' . urlencode($fltr->get_attribute_alias()) . ' = "' . $fltr->get_comparator() . '"+' . $linked_element->build_js_value_getter($link->get_column_id()) . ';';
				} else {
					//filter has a static value
					$filters[] = 'param.fltr' . str_pad($fltrId++, 2, 0, STR_PAD_LEFT) . '_' . urlencode($fltr->get_attribute_alias()) . ' = "' . $fltr->get_comparator() . urlencode(strpos($fltr->get_value(), '=') === 0 ? '' : $fltr->get_value()) . '";';
				}
			}
		}
		$filters_script = implode("\n\t\t\t\t\t\t", $filters);
		// Add value filter (to show proper label for a set value)
		$value_filters = [];
		$value_filters[] = 'param.fltr' . str_pad($fltrId++, 2, 0, STR_PAD_LEFT) . '_' . $widget->get_value_column()->get_data_column_name() . ' = $("#' . $this->get_id() . '").combogrid("getValues").join();';
		$value_filters_script = implode("\n\t\t\t\t\t\t", $value_filters);
		
		// firstLoadScript:			enthaelt Anweisungen, die nur beim ersten Laden ausgefuehrt
		// 							werden sollen (Initialisierung)
		// filters_script:			enthaelt Anweisungen, welche die gesetzten Filter zur Anfrage
		// 							hinzufuegen
		// value_filters_script:	enthaelt Anweisungen, welche einen Filter zur Anfrage hinzu-
		// 							fuegt, welcher auf dem aktuell gesetzten Wert beruht
		
		// paramGlobal.
		// _firstLoad:				ist nur beim ersten Laden gesetzt
		// _jsValueSetterUpdate:	ist gesetzt wenn der Wert durch den Value-Setter gesetzt wurde
		// 							und der autosuggest-Inhalt neu geladen werden soll
		// _jsFilterSetterUpdate:	ist gesetzt wenn sich verlinkte Filter geaendert haben und
		// 							der autosuggest-Inhalt neu geladen werden soll
		
		$output = '
					var paramGlobal = $(this).datagrid("options").queryParams;
					
					if (paramGlobal._firstLoad == undefined){
						paramGlobal._firstLoad = true;
					} else if (paramGlobal._firstLoad == true) {
						paramGlobal._firstLoad = false;
					}
					
					if (paramGlobal._jsValueSetterUpdate) {
						' . $value_filters_script . '
					} else if (paramGlobal._jsFilterSetterUpdate) {
						' . $filters_script . '
						' . $value_filters_script . '
					} else if (paramGlobal._firstLoad) {
						paramGlobal._firstLoad = false;
						' . $first_load_script . '
					} else {
						' . $filters_script . '
						if (!param.q) {
							param.q = $("#' . $this->get_id() . '").combogrid("getText");
						}
					}
					';
		
		return $output;
	}
	
	/**
	 * Creates the JavaScript-Code which is executed after loading the autosuggest-
	 * data. All filters are removed as their values can change because of live-
	 * references (filters are added again before the next loading). The function
	 * can be modified by passing jsValueSetter- and jsFilterSetter-scripts.
	 * 
	 * @param string $jsValueSetterScript javascript-code which is executed after a
	 * 	value-setter-update.
	 * @param string $jsFilterSetterScript javascript-code which is executed after a
	 * 	filter-setter-update.
	 * @return string
	 */
	private function build_js_on_load_live_reference($jsValueSetterScript = null, $jsFilterSetterScript = null) {
		$output = '
					var dataUrlParams = $("#' . $this->get_id() . '").combogrid("grid").datagrid("options").queryParams;
					
					for (key in dataUrlParams) {
						if (key.substring(0, 4) == "fltr") {
							delete dataUrlParams[key];
						}
					}
					if (dataUrlParams.q) {
						delete dataUrlParams.q;
					}
					if (dataUrlParams._firstLoad) {
						dataUrlParams._firstLoad = false;
					}
					if (dataUrlParams._jsFilterSetterUpdate) {
						delete dataUrlParams._jsFilterSetterUpdate;
						
						' . $jsFilterSetterScript . '
					}
					if (dataUrlParams._jsValueSetterUpdate) {
						delete dataUrlParams._jsValueSetterUpdate;
						
						' . $jsValueSetterScript . '
					}';
		
		return $output;
	}
	
	/**
	 * Creates javascript-code which is executed after the successful loading of auto-
	 * suggest-data.
	 * 
	 * @return string
	 */
	function build_js_on_load_sucess_live_reference() {
		$textColumnName = $this->get_widget()->get_text_column()->get_data_column_name();
		
		// Nach einem Value-Setter-Update wird der Text neu gesetzt um das Label ordentlich
		// anzuzeigen und das onChange-Skript wird ausgefuehrt.
		$jsValueSetterScript = '
						var selectedrow = $("#' . $this->get_id() .'").combogrid("grid").datagrid("getSelected");
						if (selectedrow != null) {
							$("#' . $this->get_id() . '").combogrid("setText", selectedrow["' . $textColumnName . '"]);
						}
						
						' . $this->get_id() . '_onChange();';
		
		// Nach einem Filter-Setter-Update wird geprueft ob sich die gesetzten Filter und der
		// gesetzte Wert widersprechen.
		$jsFilterSetterScript = '
						var rows = $("#' . $this->get_id() . '").combogrid("grid").datagrid("getData");
						// Ergibt die Anfrage bei einem FilterSetterUpdate keine Ergebnisse ist wahrscheinlich
						// ein Wert gesetzt, welcher den gesetzten Filtern widerspricht. Deshalb wird der Wert
						// der ComboTable geloescht und anschliessend neu geladen.
						if (rows["total"] == 0) {
							// Bei diesem Leeren der ComboTable werden keine Referenzen aktualisiert.
							$("#' . $this->get_id() . '").combogrid("grid").datagrid("options").queryParams.suppressAllUpdates = true;
							$("#' . $this->get_id() . '").combogrid("clear");
							$("#' . $this->get_id() . '").combogrid("grid").datagrid("reload");
						}';
		
		return $this->build_js_on_load_live_reference($jsValueSetterScript, $jsFilterSetterScript);
	}
	
	/**
	 * Creates javascript-code which is executed after the erroneous loading of auto-
	 * suggest-data.
	 * 
	 * @return string
	 */
	function build_js_on_load_error_live_reference() {
		return $this->build_js_on_load_live_reference();
	}
	
	/**
	 * Creates javascript-code which is executed after successful loading of autosuggest-
	 * data. If autoselect_single_result is true, a single return value from autosuggest
	 * is automatically selected.
	 * 
	 * @return string
	 */
	function build_js_on_load_success_autoselect() {
		$widget = $this->get_widget();
		
		$output = '';
		if ($widget->get_autoselect_single_result()) {
			$uidColumnName = $widget->get_table()->get_uid_column()->get_data_column_name();
			$textColumnName = $widget->get_text_column()->get_data_column_name();
			
			$output = '
					var ' . $this->get_id() . '_cg = $("#' . $this->get_id() . '");
					var rows = ' . $this->get_id() . '_cg.combogrid("grid").datagrid("getData");
					if (rows["total"] == 1) {
						var selectedrow = ' . $this->get_id() . '_cg.combogrid("grid").datagrid("getSelected");
						if (selectedrow == null || selectedrow["' . $uidColumnName . '"] != rows["rows"][0]["' . $uidColumnName . '"]) {
							' . $this->get_id() . '_cg.combogrid("grid").datagrid("selectRow", 0);
							' . $this->get_id() . '_cg.combogrid("setText", rows["rows"][0]["' . $textColumnName . '"]);
							' . $this->get_id() . '_cg.combogrid("hidePanel");
						}
					}';
		}
		
		return $output;
	}
}
?>