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
		// Need to understand, if it's the first time loading to prevent that loading if a value is set already
		$output .= '$("#' . $this->get_id() . '").combogrid({';
		$output .= $this->build_js_init_options();
		$output .= '});';
		
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
						' . ($this->get_on_change_script() ? ', onChange: function(newValue, oldValue) {
							if (!newValue) {
								// Loeschen der verlinkten Elemente wenn der Wert manuell geloescht wird
								// Die Updates der Filter-Links werden an dieser Stelle unterdrueckt und
								// nur einmal nach dem value-Setter update onLoadSuccess ausgefuehrt.
								var suppressFilterSetterUpdate = true;
								' . $this->get_on_change_script() . '
							}
						}
						, onSelect: function(index, row) {
							' . $this->get_on_change_script() . '
						}' : '') . '
						, onShowPanel: function() {
							// Wird firstLoad verhindert, wuerde man eine leere Tabelle sehen. Um das zu
							// verhindern wird die Tabelle hier neu geladen, falls sie leer ist.
							// Update: dadurch wird bei anfaenglicher manueller Eingabe eines Wertes doppelt geladen
							if($(this).combogrid("grid").datagrid("getRows").length == 0) {
								$(this).combogrid("grid").datagrid("reload");
			                }
						}';
		return $output;
	}
	
	function build_js_value_getter($column = null, $row = null){
		if ($this->get_widget()->get_multi_select() || is_null($column) || $column === ''){
			$value_getter = <<<JS
						return {$this->get_id()}_cg.combogrid("getValues").join();
JS;
		} else {
			$value_getter = <<<JS
						var row = {$this->get_id()}_cg.combogrid("grid").datagrid("getSelected");
						if (row) { 
							if (row["{$column}"] == undefined){
								{$this->get_id()}_cg.combogrid("grid").datagrid("reload");
							}
							return row["{$column}"]; 
						} else { 
							return ""; 
						}
JS;
		}
		
		$output = <<<JS
				(function() {
					var {$this->get_id()}_cg = $("#{$this->get_id()}");
					if ({$this->get_id()}_cg.data("combogrid")) {
						{$value_getter}
					} else {
						return $("#{$this->get_id()}").val();
					}
				})()

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
		$widget = $this->get_widget();
		
		$output = '
							(function() {
								var ' . $this->get_id() . '_cg = $("#' . $this->get_id() . '");
								var value = ' . $value . ', valueArray;
								if (' . $this->get_id() . '_cg.data("combogrid")) {
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
									if (!' . $this->get_id() . '_cg.combogrid("getValues").equals(valueArray)) {';
		
		if ($this->get_widget()->get_multi_select()) {
			$output .= '
										' . $this->get_id() . '_cg.combogrid("setValues", valueArray);';
		} else {
			$output .= '
										if (valueArray.length <= 1) {
											' . $this->get_id() . '_cg.combogrid("setValues", valueArray);
										}';
		}
		
		$output .= '
										' . $this->get_id() . '_cg.combogrid("grid").datagrid("options").queryParams._jsValueSetterUpdate = true;
										' . $this->get_id() . '_cg.combogrid("grid").datagrid("reload");
									}
								} else {
									$("#' . $this->get_id() . '").val(value).trigger("change");
								}
							})()';
		
		return $output;
	}
	
	/**
	 * Erzeugt den JavaScript-Code welcher vor dem Laden des MagicSuggest-Inhalts
	 * ausgefuehrt wird. Wurde programmatisch ein Wert gesetzt, wird als Filter
	 * nur dieser Wert hinzugefuegt, um das Label ordentlich anzuzeigen. Sonst werden
	 * die am Widget definierten Filter gesetzt. Die Filter werden nach dem Laden
	 * wieder entfernt, da sich die Werte durch Live-Referenzen aendern koennen.
	 *
	 * @return string
	 */
	function build_js_on_beforeload_live_reference() {
		$widget = $this->get_widget();
		
		// Prevent loading data from backend if the value and value_text are set already or there is
		// no value and thus no need to search for anything.
		// The trouble here is, that if the first loading is prevented, the next time the user clicks on the dropdown button,
		// an empty table will be shown, because the last result is cached. To fix this, we bind a reload of the table to
		// onShowPanel in case the grid is empty (see below).
		if (!is_null($this->get_value_with_defaults()) && $this->get_value_with_defaults() !== ''){
			if ($widget->get_value_text()){
				// If the text is already known, set it an prevent initial backend request
				$first_load_script = "$('#" . $this->get_id() ."')." . $this->get_element_type() . '("setText", "' . str_replace('"', '\"', $widget->get_value_text()) . '"); return false;';
			} else {
				// If there is a value, but no text, add a filter over the UID column with this value and do not prevent the initial autoload
				$first_load_script = "param.fltr01_" . $widget->get_value_column()->get_data_column_name() . " = '" . $this->get_value_with_defaults() . "';";
			}
		} else {
			// If no value set, just supress initial autoload
			$first_load_script = "return false;";
		}
		
		// If the value is set data is loaded from the backend. Same if also value-text is set, because otherwise
		// live-references don't work at the beginning. If no value is set, loading from the backend is prevented.
		// The trouble here is, that if the first loading is prevented, the next time the user clicks on the dropdown button,
		// an empty table will be shown, because the last result is cached. To fix this, we bind a reload of the table to
		// onShowPanel in case the grid is empty (see above).
		if (!is_null($this->get_value_with_defaults()) && $this->get_value_with_defaults() !== ''){
			if ($widget->get_value_text()){
				// If the text is already known, set it and prevent initial backend request
				$first_load_script = "$('#" . $this->get_id() ."')." . $this->get_element_type() . '("setText", "' . str_replace('"', '\"', $widget->get_value_text()) . '"); return false;';
			} else {
				$first_load_script = '
						paramGlobal._jsValueSetterUpdate = true;
						param.fltr01_' . $widget->get_value_column()->get_data_column_name() . ' = "' . $this->get_value_with_defaults() . '";';
			}
		} else {
			// If no value set, just supress initial autoload
			$first_load_script = '
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
		
		$output = '
					var paramGlobal = $(this).datagrid("options").queryParams;
					
					if (paramGlobal._firstLoad == undefined){
						paramGlobal._firstLoad = 1;
					} else if (parseInt(paramGlobal._firstLoad) == 1) {
						paramGlobal._firstLoad = 0;
					}
					
					if (paramGlobal._jsValueSetterUpdate) {
						' . $value_filters_script . '
					} else if (paramGlobal._jsFilterSetterUpdate) {
						' . $filters_script . '
						' . $value_filters_script . '
					} else if (paramGlobal._firstLoad) {
						paramGlobal._firstLoad = 0;
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
	 * Erzeugt den JavaScript-Code welcher nach dem Laden des MagicSuggest-Inhalts
	 * ausgefuehrt wird. Alle gesetzten Filter werden entfernt, da sich die Werte
	 * durch Live-Referenzen aendern koennen (werden vor dem naechsten Laden wieder
	 * hinzugefuegt). Wurde der Wert zuvor programmatisch gesetzt, wird er neu
	 * gesetzt um das Label ordentlich anzuzeigen. Nach der Erzeugung von MagicSuggest
	 * werden initiale Werte gesetzt und neu geladen.
	 *
	 * @return string
	 */
	function build_js_on_load_sucess_live_reference() {
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
						dataUrlParams._firstLoad = 0;
					}
					if (dataUrlParams._jsFilterSetterUpdate) {
						delete dataUrlParams._jsFilterSetterUpdate;
					}
					if (dataUrlParams._jsValueSetterUpdate) {
						// es gibt sonst Konstellationen, in denen nur die Oid angezeigt wird
						// (Tastatureingabe, dann aber keine Auswahl, anschliessend value-Setter update)
						// Update: leider wird hierbei zweimal onChange getriggert
						//var value = $("#' . $this->get_id() . '").combogrid("getValues");
						//$("#' . $this->get_id() . '").combogrid("clear");
						//$("#' . $this->get_id() . '").combogrid("setValues", value);
						
						' . $this->get_on_change_script() . '
						
						delete dataUrlParams._jsValueSetterUpdate;
					}';
		
		return $output;
	}
	
	function build_js_on_load_error_live_reference() {
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
						dataUrlParams._firstLoad = 0;
					}
					if (dataUrlParams._jsFilterSetterUpdate) {
						delete dataUrlParams._jsFilterSetterUpdate;
					}
					if (dataUrlParams._jsValueSetterUpdate) {
						// es gibt sonst Konstellationen, in denen nur die Oid angezeigt wird
						// (Tastatureingabe, dann aber keine Auswahl, anschliessend value-Setter update)
						// Update: leider wird hierbei zweimal onChange getriggert
						//var value = $("#' . $this->get_id() . '").combogrid("getValues");
						//$("#' . $this->get_id() . '").combogrid("clear");
						//$("#' . $this->get_id() . '").combogrid("setValues", value);
						
						delete dataUrlParams._jsValueSetterUpdate;
					}';
		
		return $output;
	}
}
?>