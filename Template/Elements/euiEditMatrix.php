<?php
namespace exface\JEasyUiTemplate\Template\Elements;
use exface\Core\DataTypes\AbstractDataType;
class euiEditMatrix extends euiDataMatrixOld {
	private $label_values = array();
	
	protected function init(){
		parent::init();
		$this->set_element_type('datagrid');
	}
	
	function generate_headers(){
		// handsontable
		$includes = array (
				'<script src="exface/vendor/exface/JEasyUiTemplate/Template/js/handsontable-rulejs/bower_components/handsontable/dist/handsontable.full.min.js"></script>',
				'<link rel="stylesheet" media="screen" href="exface/vendor/exface/JEasyUiTemplate/Template/js/handsontable-rulejs/bower_components/handsontable/dist/handsontable.full.min.css">'
				);
		// formula suppoert
		if ($this->get_widget()->get_formulas_enabled()){
			$includes[] = '<link rel="stylesheet" media="screen" href="exface/vendor/exface/JEasyUiTemplate/Template/js/handsontable-rulejs/src/handsontable.formula.css">';
			$includes[] = '<script type="text/javascript" src="exface/vendor/exface/JEasyUiTemplate/Template/js/handsontable-rulejs/bower_components/ruleJS/dist/full/ruleJS.all.full.min.js"></script>';
			$includes[] = '<script type="text/javascript" src="exface/vendor/exface/JEasyUiTemplate/Template/js/handsontable-rulejs/src/handsontable.formula.js"></script>';	
			
			$this->get_widget()->set_show_row_numbers(true);
		}
		// masonry for filter alignment
		$includes[] = '<script type="text/javascript" src="exface/vendor/bower-asset/masonry/dist/masonry.pkgd.min.js"></script>';
		return $includes;
	}
	
	function generate_html(){
		$widget = $this->get_widget();
		$output = '';
		
		// add filters
		if ($widget->has_filters()){
			foreach ($widget->get_filters() as $fltr){
				$fltr_html .= $this->get_template()->generate_html($fltr);
			}
		}
		
		// add buttons
		if ($widget->has_buttons()){
			foreach ($widget->get_buttons() as $button){
				$button_html .= $this->get_template()->generate_html($button);
			}
		}
		
		// create a container for the toolbar
		if (!$widget->get_hide_toolbar_top() && ($widget->has_filters() || $widget->has_buttons())){
			$output .= '<div id="' . $this->get_toolbar_id() . '" class="datagrid-toolbar">';
			if ($fltr_html){
				$output .= '<div class="datagrid-filters">' . $fltr_html . '</div>';
			}
			$output .= '<div style="min-height: 30px;">';
			if ($button_html) {
				$output .= $button_html;
			}
			$output .= '<a style="position: absolute; right: 0; margin: 0 4px;" href="#" class="easyui-linkbutton" iconCls="icon-search" onclick="' . $this->build_js_function_prefix() . 'doSearch()">Search</a></div>';
			$output .= '</div>';
		}
		// now the table itself
		$output .= '<div id="' . $this->get_id() . '"></div>';
		return $output;
	}
	
	function generate_js(){
		$widget = $this->get_widget();
		$output = '			
			$("#' . $this->get_id() . '").handsontable({
              ' . $this->build_js_init_options_head() . '
            });
				';
		
		// doSearch function for the filters
		if ($widget->has_filters()){
			foreach($widget->get_filters() as $fnr => $fltr){
				$fltr_impl = $this->get_template()->get_element($fltr, $this->get_page_id());
				$output .= $fltr_impl->generate_js();
				$fltrs[] = '"fltr' . str_pad($fnr, 2, 0, STR_PAD_LEFT) . '_' . urlencode($fltr->get_attribute_alias()) . '": ' . $fltr_impl->build_js_value_getter();
			}
			// build JS for the search function
			$output .= '
						function ' . $this->build_js_function_prefix() . 'doSearch(){
							$("#' . $this->get_id() . '").' . $this->get_element_type() . '("load",{' . implode(', ', $fltrs) . ', resource: "' . $this->get_page_id() . '", element: "' .  $this->get_widget()->get_id() . '"});
						}';
		}
		
		// align the filters
		$output .= "$('#" . $this->get_toolbar_id() . " .datagrid-filters').masonry({itemSelector: '.fitem', columnWidth: " . $this->get_width_relative_unit() . "});";
	
		return $output;
	}
	
	public function build_js_init_options_head() {
		$widget = $this->get_widget();
		
		$output = $this->build_js_data_source()
				. ', columnSorting: true'
				. ', sortIndicator: true'
				. ', manualColumnResize: true'
				. ', manualColumnMove: true'
				//. ', stretchH: "all"'
				. ($widget->get_show_row_numbers() ? ', rowHeaders: true' : '')
				. ($widget->get_formulas_enabled() ? ', formulas: true' : '')
				. ($this->get_width() ? ', width: ' . $this->get_width() : '')
				. ($widget->get_caption() ? ', title: "' . $widget->get_caption() . '"' : '')
				. ', ' . $this->build_js_init_options_columns()
		;
		return $output;
	}
	
	/**
	 * This special column renderer for the matrix replaces the column specified by label_column_id with a set of new columns for
	 * every unique value in the column specified by data_column_id. The new columns retain most properties of the replaced label column.
	 * @see \exface\JEasyUiTemplate\Template\Elements\grid::build_js_init_options_columns()
	 */
	public function build_js_init_options_columns(array $cols = null){
		$widget = $this->get_widget();
		$output = '';
		if (!$cols){
			$cols = $this->get_widget()->get_columns();
		}
		$column_counter = 0;
		$headers = array();
		$columns = array();
		foreach ($cols as $col){
			if ($col->get_id() == $widget->get_label_column_id()){
				foreach ($this->label_values as $val){
					$headers[] = $this->render_column_name($column_counter, $val);
					$column_counter++;
				}
			} elseif ($col->get_id() == $widget->get_data_column_id()){
				foreach ($this->label_values as $val){
					$column_name = \exface\Core\CommonLogic\DataSheets\DataColumn::sanitize_column_name($val);
					$columns[] = '{data: "' . $column_name . '", ' . $this->render_data_type($col->get_data_type()) . '}';
				}
			} else {
				$headers[] = $this->render_column_name($column_counter, $col->get_caption());
				$columns[] = '{data: "' . $col->get_data_column_name() . '", readOnly: true}';
			}
			$column_counter++;
		}
		
		$output = '
				  colHeaders: ["' . implode('","', $headers) . '"]
				, columns: [' . implode(',', $columns) . ']
				';
		
		return $output;
	}
	
	protected function render_column_name($column_number, $name){
		if (!$this->get_widget()->get_formulas_enabled()) return $name;
		$column_letters = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
		return $name . ' (' . $column_letters[$column_number] . ')';
	}
	
	/**
	 * This special data source renderer fetches data according to the filters an reorganizes the rows and column to fit the matrix.
	 * It basically transposes the data column (data_column_id) using values of the label column (label_column_id) as new column headers.
	 * The other columns remain untouched.
	 * @see \exface\JEasyUiTemplate\Template\Elements\grid::build_js_data_source()
	 */
	public function build_js_data_source(){
		$widget = $this->get_widget();
		$visible_columns = array();
		$output = '';
		$result = array();
	
		// create data sheet to fetch data
		$ds = $this->get_template()->get_workbench()->data()->create_data_sheet($this->get_meta_object());
		// add columns
		foreach ($widget->get_columns() as $col){
			$ds->get_columns()->add_from_expression($col->get_attribute_alias(), $col->get_data_column_name(), $col->is_hidden());
			if (!$col->is_hidden()) $visible_columns[] = $col->get_data_column_name();
		}
		// add the filters
		foreach ($widget->get_filters() as $fw){
			if (!is_null($fw->get_value())){
				$ds->add_filter_from_string($fw->get_attribute_alias(), $fw->get_value());
			}
		}
		// add the sorters
		foreach ($widget->get_sorters() as $sort){
			$ds->get_sorters()->add_from_string($sort->attribute_alias, $sort->direction);
		}
		// add aggregators
		foreach ($widget->get_aggregations() as $aggr){
			$ds->get_aggregators()->add_from_string($aggr);
		}
	
		// get the data
		$ds->data_read();
		$label_col = $widget->get_label_column();
		$data_col = $widget->get_data_column();
		foreach ($ds->get_rows() as $nr => $row){
			$new_row_id = null;
			$new_row = array();
			$new_col_val = null;
			$new_col_id = null;
			foreach ($row as $fld => $val){
				
				if ($fld === $label_col->get_data_column_name()){
					$new_col_id = $val;
					// TODO we probably need a special parameter for sorting labels!
					if (!in_array($val, $this->label_values)) $this->label_values[] = $val;
				} elseif ($fld === $data_col->get_data_column_name()){
					$new_col_val = $val; 
				} elseif (in_array($fld, $visible_columns)) {
					$new_row_id .= $val;
					$new_row[$fld] = $val;
				}
			}
			if (!is_array($result[$new_row_id])){
				$result[$new_row_id] = $new_row;
			}
			$result[$new_row_id][$new_col_id] = $new_col_val;
		}
		
		$output = "data: [";
		foreach ($result as $row){
			$output .= "{";
			foreach ($row as $fld => $val){
				$val = str_replace('"', '\"', $val);
				if (!is_numeric($val)){
					$val = '"' . $val . '"';
				}
				$output .= '"' . $this->clean_id($fld) . '": ' . $val .',';
			}
			$output = substr($output, 0, -1);
			$output .= '},';
		}
		$output = substr($output, 0, -1);
		$output .= ']';

		return $output;
	}
	
	public function render_data_type(AbstractDataType $data_type){
		if ($data_type->is(EXF_DATA_TYPE_BOOLEAN)) {
			return 'type: "checkbox"';
		} elseif ($data_type->is(EXF_DATA_TYPE_DATE)){
			return 'type: "date"';
		} elseif ($data_type->is(EXF_DATA_TYPE_PRICE)){ 
			return 'type: "numeric", format: "0.00"';
		} elseif ($data_type->is(EXF_DATA_TYPE_NUMBER)){
			return 'type: "numeric"';
		} else {
			return 'type: "text"';
		}
	}
}
?>