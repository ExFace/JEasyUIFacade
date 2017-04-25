<?php
namespace exface\JEasyUiTemplate\Template\Elements;
use exface\Core\Widgets\DataColumnGroup;
use exface\Core\Widgets\Data;
use exface\Core\CommonLogic\DataSheets\DataSheet;

/**
 * Implementation of a basic grid.
 * 
 * @method Data get_widget()
 * 
 * @author Andrej Kabachnik
 *
 */
class euiData extends euiAbstractElement {
	private $toolbar_id = null;
	private $show_footer = null;
	private $editable = false;
	private $editors = array();
	private $on_before_load = '';
	private $on_load_success = '';
	private $on_load_error = '';
	private $load_filter_script = '';
	private $headers_colspan = array();
	private $headers_rowspan = array();
	
	public function generate_html(){
		return '';
	}
	
	public function generate_js(){
		return '';
	}
	
	protected function init(){
		/* @var $col \exface\Core\Widgets\DataColumn */
		foreach ($this->get_widget()->get_columns() as $col){
			// handle editors
			if ($col->is_editable()){
				$editor = $this->get_template()->get_element($col->get_editor(), $this->get_page_id());
				$this->set_editable(true);
				$this->editors[$col->get_id()] = $editor;
			}
		}
	}
	
	/**
	 * Generates config-elements for the js grid instatiator, that define the data source for the grid.
	 * By default the data source is remote and will be fetched via AJAX. Override this method for local data sources.
	 * @return string
	 */
	public function build_js_data_source(){
		$widget = $this->get_widget();
		
		if ($widget->get_lazy_loading()){
			// Lazy loading via AJAX
			$params = array();
			$queryParams = array(
					'resource' => $this->get_page_id(),
					'element' => $widget->get_id(),
					'object' => $this->get_widget()->get_meta_object()->get_id(),
					'action' => $widget->get_lazy_loading_action()
			);
			foreach($queryParams as $param => $val){
				$params[] = $param . ': "' . $val . '"';
			}
			
			// Add initial filters
			if ($this->get_widget()->has_filters()){
				foreach ($this->get_widget()->get_filters() as $fnr => $fltr){
					// If the filter is a live reference, add the code to use it to the onBeforeLoad event
					if ($fltr->get_value_expression() && $fltr->get_value_expression()->is_reference()){
							$link = $fltr->get_value_expression()->get_widget_link();
							$linked_element = $this->get_template()->get_element_by_widget_id($link->get_widget_id(), $this->get_page_id());
							$live_filter_js .= 'param.fltr' . str_pad($fnr, 2, 0, STR_PAD_LEFT) . '_' . urlencode($fltr->get_attribute_alias()) . '= "' . $fltr->get_comparator() . '"+' . $linked_element->build_js_value_getter() . ';';
							$this->add_on_before_load($live_filter_js);
					} 
					// If the filter has a static value, just set it here
					else {
						$params[] = 'fltr' . str_pad($fnr, 2, 0, STR_PAD_LEFT) . '_' . urlencode($fltr->get_attribute_alias()) . ': "' . $fltr->get_comparator() . urlencode(strpos($fltr->get_value(), '=') === 0 ? '' : $fltr->get_value()) . '"';
					}
				}
			}
			$result = '
				url: "' . $this->get_ajax_url() . '"
				, queryParams: {' . implode("\n\t\t\t\t\t, ", $params) . '}';
		} else {
			// Data embedded in the code of the DataGrid
			$data = $widget->prepare_data_sheet_to_read();
			$data->data_read();
			$result = '
				remoteSort: false
				, loader: function(param, success, error) {' . $this->build_js_data_loader_without_ajax($data) . '}';
		}
		
		return $result;
	}
	
	public function build_js_init_options_head() {
		/* @var $widget \exface\Core\Widgets\Data */
		$widget = $this->get_widget();
		
		// add initial sorters
		$sort_by = array();
		$direction = array();
		if ($widget->get_lazy_loading() && count($widget->get_sorters()) > 0){
			foreach ($widget->get_sorters() as $sort){
				$sort_by[] = urlencode($sort->attribute_alias);
				$direction[] = urlencode($sort->direction);
			}
			$sortColumn = ", sortName: '" . implode(',', $sort_by) . "'";
			$sortOrder = ", sortOrder: '" . implode(',', $direction) . "'";
		}
		
		// Make sure, all selections are cleared, when the data is loaded from the backend. This ensures, the selected rows are always visible to the user!
		if ($widget->get_multi_select()){
			$this->add_on_load_success('$(this).' . $this->get_element_type() . '("clearSelections");');
		}
		
		$output = '
				, rownumbers: ' . ($widget->get_show_row_numbers() ? 'true' : 'false') . '
				, fitColumns: true
				, multiSort: ' . ($widget->get_header_sort_multiple() ? 'true' : 'false') .'
				' . $sortColumn . $sortOrder . '
				, showFooter: "' . ($this->get_show_footer() ? 'true' : 'false' ) . '"
				' . ($widget->get_uid_column_id() ? ', idField: "' . $widget->get_uid_column()->get_data_column_name() . '"' : '') . '
				' . (!$widget->get_multi_select() ? ', singleSelect: true' : '') . '
				' . ($this->get_width() ? ', width: "' . $this->get_width() . '"' : '') . '
				, pagination: ' . ($widget->get_paginate() ? 'true' : 'false') . '
				, pageList: ' . json_encode($this->get_template()->get_app()->get_config()->get_option('WIDGET.DATATABLE.PAGE_SIZES_SELECTABLE')) . '
				, pageSize: ' . $widget->get_paginate_default_page_size() . '
				, striped: "' . ($widget->get_striped() ? 'true' : 'false') . '"
				, nowrap: "' . ($widget->get_nowrap() ? 'true' : 'false') . '"
				, toolbar: "#' . $this->get_toolbar_id() . '"
				' . ($this->get_on_before_load() ? ', onBeforeLoad: function(param) {
					' . $this->get_on_before_load() . '
				}' : '') . '
				' . ($this->get_on_load_success() ? ', onLoadSuccess: function(data) {
					' . $this->get_on_load_success() . '
				}' : '') . '
				, onLoadError: function(response) {
					' . $this->build_js_show_error('response.responseText', 'response.status + " " + response.statusText') . '
					' . $this->get_on_load_error() . '
				}
				' . ($this->get_load_filter_script() ? ', loadFilter: function(data) {
					' . $this->get_load_filter_script() . '
					return data;
				}' : '') . '
				, columns: [ ' .  implode(',', $this->build_js_init_options_columns()) . ' ]';
		return $output;
	}
	
	public function build_js_init_options_columns(array $column_groups = null){
		if (!$column_groups){
			$column_groups = $this->get_widget()->get_column_groups();
		}
		
		// render the columns
		$header_rows = array();
		$full_height_column_groups = array();
		if ($this->get_widget()->get_multi_select()){
			$header_rows[0][0] = '{field: "ck", checkbox: true}';
		}
		/* @var $column_group \exface\Core\Widgets\DataColumnGroup */
		// Set the rowspan for column groups with a caption and remember those without a caption to set the colspan later
		foreach ($column_groups as $column_group){
			if (!$column_group->get_caption()){
				$full_height_column_groups[] = $column_group;
			}
		}
		// Now set colspan = 2 for all full height columns, if there are two rows of columns
		if (count($full_height_column_groups) != count($column_groups)){
			foreach ($full_height_column_groups as $column_group){
				$this->set_column_header_rowspan($column_group, 2);
			}
			if ($this->get_widget()->get_multi_select()){
				$header_rows[0][0] = '{field: "ck", checkbox: true, rowspan: 2}';
			}
		} 
		// Now loop through all column groups again and built the header definition
		foreach ($column_groups as $column_group){
			if ($column_group->get_caption()){
				$header_rows[0][] = '{title: "' . $column_group->get_caption() . '", colspan: ' . $column_group->count_columns_visible() . '}';
				$put_into_header_row = 1;
			} else {
				$put_into_header_row = 0;
			}
			foreach ($column_group->get_columns() as $col){
				$header_rows[$put_into_header_row][] = $this->build_js_init_options_column($col);
				if ($col->has_footer()) $this->set_show_footer(true);
			}
		}
		
		foreach ($header_rows as $i => $row){
			$header_rows[$i] = '[' . implode(',', $row) . ']';
		}
		
		return $header_rows;
	}
	
	protected function set_column_header_colspan(DataColumnGroup $column_group, $colspan){
		foreach ($column_group->get_columns() as $col){
			$this->headers_colspan[$col->get_id()] = $colspan;
		}
		return $this;
	}
	
	protected function get_column_header_colspan($column_id){
		return $this->headers_colspan[$column_id];
	}
	
	protected function set_column_header_rowspan(DataColumnGroup $column_group, $rowspan){
		foreach ($column_group->get_columns() as $col){
			$this->headers_rowspan[$col->get_id()] = $rowspan;
		}
		return $this;
	}
	
	protected function get_column_header_rowspan($column_id){
		return $this->headers_rowspan[$column_id];
	}
	
	public function build_js_init_options_column (\exface\Core\Widgets\DataColumn $col){
		// set defaults
		$editor = $this->editors[$col->get_id()];
		// TODO Settig "field" to the id of the column is dirty, since the data sheet column has 
		// the attribute name for id. I don't know, why this actually works, because the field in the
		// JSON is named by attribute id, not column id. However, when getting the data from the table
		// via java script, the fields are named by the column id (as configured here).
		
		// FIXME Make compatible with column groups
		$colspan = $this->get_column_header_colspan($col->get_id());
		$rowspan = $this->get_column_header_rowspan($col->get_id());
		$output = '{
							title: "<span title=\"' . $this->build_hint_text($col->get_hint(), true) . '\">' . $col->get_caption() . '</span>"'
							. ($col->get_attribute_alias() ? ', field: "' . $col->get_data_column_name() . '"' : '')
							. ($colspan ? ', colspan: ' . intval($colspan) : '')
							. ($rowspan ? ', rowspan: ' . intval($rowspan) : '')
							. ($col->is_hidden() ? ', hidden: true' :  '')
							. ($col->get_width()->is_template_specific() ? ', width: "' . $col->get_width()->to_string() . '"' : '')
							. ($editor ? ', editor: {type: "' . $editor->get_element_type() . '"' . ($editor->build_js_init_options() ? ', options: {' . $editor->build_js_init_options() . '}' : '') . '}' : '')
							. ($col->get_cell_styler_script() ? ', styler: function(value,row,index){' . $col->get_cell_styler_script() . '}' :  '')
							. ', align: "' . $col->get_align() . '"'
							. ', sortable: ' . ($col->get_sortable() ? 'true' : 'false') 
							. '}';
	
		return $output;
	}
	
	public function get_toolbar_id() {
		if (is_null($this->toolbar_id)){
			$this->toolbar_id = $this->get_id() . '_toolbar';
		}
		return $this->toolbar_id;
	}
	
	public function set_toolbar_id($value) {
		$this->toolbar_id = $value;
	}
	
	public function get_show_footer() {
		if (is_null($this->show_footer)){
			$this->show_footer = ($this->get_template()->get_config()->get_option('DATAGRID_SHOW_FOOTER_BY_DEFAULT') ? true : false);
		}
		return $this->show_footer;
	}
	
	public function set_show_footer($value) {
		$this->show_footer = $value;
	}
	
	public function is_editable() {
		return $this->editable;
	}
	
	public function set_editable($value) {
		$this->editable = $value;
	}

	public function get_editors(){
		return $this->editors;
	}
	
	/**
	 * Add JS code to be executed on the OnBeforeLoad event of jEasyUI datagrid. The script will have access to the "param" variable
	 * representing all XHR parameters to be sent to the server.
	 * 
	 * @param string $script
	 */
	public function add_on_before_load($script){
		$this->on_before_load .= $script;
	}
	
	/**
	 * Set JS code to be executed on the OnBeforeLoad event of jEasyUI datagrid. The script will have access to the "param" variable
	 * representing all XHR parameters to be sent to the server.
	 *
	 * @param string $script
	 */
	public function set_on_before_load($script){
		$this->on_before_load = $script;
	}
	
	protected function get_on_before_load(){
		$script = <<<JS
				if ($(this).{$this->get_element_type()}('options')._skipNextLoad == true) {
					$(this).{$this->get_element_type()}('options')._skipNextLoad = false;
					return false;
				}
				{$this->on_before_load}
JS;
		return $script;
	}
	
	/**
	 * Binds a script to the onLoadSuccess event.
	 * @param string $script
	 */
	public function add_on_load_success($script){
		$this->on_load_success .= $script;
	}
	
	protected function get_on_load_success(){
		return $this->on_load_success;
	}
	
	/**
	 * Binds a script to the onLoadError event.
	 * @param string $script
	 */
	public function add_on_load_error($script) {
		$this->on_load_error .= $script;
	}
	
	protected function get_on_load_error(){
		return $this->on_load_error;
	}
	
	public function add_on_change_script($string){
		return $this->add_on_load_success($string);
	}
	
	public function add_load_filter_script($javascript){
		$this->load_filter_script .= $javascript;
	}
	
	public function get_load_filter_script(){
		return $this->load_filter_script;
	}
	
	public function build_js_data_loader_without_ajax(DataSheet $data){
		$js = <<<JS
		
		try {
			var data = {$this->get_template()->encode_data($this->prepare_data($data))};
		} catch (err){
			error();
			return;
		}
		
		var filter, value, total = data.rows.length;
		for(var p in param){
			if (p.startsWith("fltr")){
				column = p.substring(7);	
				value = param[p];
			}
			
			if (value){
				var regexp = new RegExp(value, 'i');
				for (var row=0; row<total; row++){
					if (data.rows[row] && typeof data.rows[row][column] !== 'undefined'){
						if (!data.rows[row][column].match(regexp)){
							data.rows.splice(row, 1);
						}
					}
				}
			}
		}
		data.total = data.rows.length;
		success(data);	
		return;
JS;
		return $js;
	}
	
	public function build_js_init_options(){
		return $this->build_js_data_source() . $this->build_js_init_options_head();
	}
	  
}
?>