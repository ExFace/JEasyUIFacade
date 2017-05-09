<?php
namespace exface\JEasyUiTemplate\Template\Elements;

use exface\Core\Widgets\DataTable;
use exface\Core\Interfaces\Actions\ActionInterface;
use exface\AbstractAjaxTemplate\Template\Elements\JqueryDataTableTrait;

class euiDataTable extends euiData {
	
	use JqueryDataTableTrait;
	
	protected function init(){
		parent::init();
		$this->set_element_type('datagrid');
		if ($refresh_link = $this->get_widget()->get_refresh_with_widget()){
			if ($refresh_link_element = $this->get_template()->get_element($refresh_link->get_widget())){
				$refresh_link_element->add_on_change_script($this->build_js_refresh());
			}
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\JEasyUiTemplate\Template\Elements\euiAbstractElement::get_widget()
	 * @return DataTable
	 */
	public function get_widget(){
		return parent::get_widget();
	}
	
	function generate_html(){
		/* @var $widget \exface\Core\Widgets\DataTable */
		$widget = $this->get_widget();
		
		// first the table itself
		$output = '<table id="' . $this->get_id() . '"></table>';
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
				$context_menu_html .= $this->get_template()->get_element($button)->build_html_button();
			}
		}

		// create a container for the toolbar
		if ($widget->has_filters() || $widget->has_buttons()){
			if ($widget->get_hide_toolbar_top()){
				$toolbar_style = 'visibility: hidden; height: 0px; padding: 0px;';
			}
			$output .= '<form id="' . $this->get_toolbar_id() . '"  style="' . $toolbar_style . '" onsubmit="' . $this->build_js_function_prefix() . 'doSearch();return false;">';
			if ($fltr_html){
				$output .= '<div class="datagrid-filters">' . $fltr_html . '</div>';
			}
			$output .= '<div style="min-height: 30px;">';
			if ($button_html) {
				$output .= $button_html;
			}
			$output .= '<button type="submit" style="position: absolute; right: 0; margin: 0 4px;" href="#" class="easyui-linkbutton" iconCls="icon-search">Search</button></div>';
			$output .= '</form>';
		}
		
		// Create a context menu if any items were found
		if ($context_menu_html && $widget->get_context_menu_enabled()){
			$output .= '<div id="' . $this->get_id() . '_cmenu" class="easyui-menu">' . $context_menu_html . '</div>';
			$output .= $button_html;
		}
		
		return $output;
	}
	
	function generate_js(){
		$widget = $this->get_widget();
		$output = '';
		
		if ($this->is_editable()){
			foreach ($this->get_editors() as $editor){
				$output .= $editor->build_js_inline_editor_init();
			}
		}
		
		$grid_head = '';
		
		// add dataGrid specific params
		// row details (expandable rows)
		if ($widget->has_row_details()){
			// Create a detail container
			/* @var $details \exface\Core\Widgets\container */
			$details = $widget->get_row_details_container();
			$details_element = $this->get_template()->get_element($widget->get_row_details_container());
			$details_height = (!$details->get_height()->is_undefined() ? ", height: '" . $details_element->get_height() . "'" : "");
			
			// Add the needed options to our datagrid
			$grid_head .= <<<JS
					, view: detailview
					, detailFormatter: function(index,row){
						return '<div id="{$details_element->get_id()}_'+row.{$widget->get_meta_object()->get_uid_alias()}+'"></div>';
					}
					, onExpandRow: function(index,row){
						$('#{$details_element->get_id()}_'+row.{$widget->get_meta_object()->get_uid_alias()}).panel({
			            	border: false,
							href: '{$this->get_ajax_url()}',
			            	method: 'post',
							queryParams: {
								action: '{$widget->get_row_details_action()}',
								resource: '{$this->get_page_id()}',
								element: '{$details->get_id()}',
								prefill: {
									oId: "{$widget->get_meta_object_id()}", 
									rows:[
										{ {$widget->get_meta_object()->get_uid_alias()} : row.{$widget->get_meta_object()->get_uid_alias()} }
									], 
									filters: {$this->build_js_data_filters()}
								},
								exfrid: row.{$widget->get_meta_object()->get_uid_alias()}
							},
							onLoad: function(){
			                   	$('#{$this->get_id()}').{$this->get_element_type()}('fixDetailRowHeight',index);
			            	},
			                onLoadError: function(response){
			                	{$this->build_js_show_error('response.responseText', 'response.status + " " + response.statusText')} 
							},
			       			onResize: function(){
			                	$('#{$this->get_id()}').{$this->get_element_type()}('fixDetailRowHeight',index);			
                    		}
			         	{$details_height}
						});
					}
JS;
		}
		
		// group rows if required
		if ($widget->has_row_groups()){
			$grid_head .= ', view: groupview'
					. ",groupField: '" . $widget->get_row_groups_by_column_id() . "'"
					. ",groupFormatter:function(value,rows){ return value" . ($widget->get_row_groups_show_count() ? " + ' (' + rows.length + ')'" : "") . ";}";
			if ($widget->get_row_groups_expand() == 'none' 
			|| $widget->get_row_groups_expand() == 'first'){
				$this->add_on_load_success("$('#" . $this->get_id() . "')." . $this->get_element_type() . "('collapseGroup');");
			} 
			if ($widget->get_row_groups_expand() == 'first'){
				$this->add_on_load_success("$('#" . $this->get_id() . "')." . $this->get_element_type() . "('expandGroup', 0);");
			}
		}
		
		// Double click actions. Currently only supports one double click action - the first one in the list of buttons
		if ($dblclick_button = $widget->get_buttons_bound_to_mouse_action(EXF_MOUSE_ACTION_DOUBLE_CLICK)[0]){
			
			$grid_head .= ', onDblClickRow: function(index, row) {' . $this->get_template()->get_element($dblclick_button)->build_js_click_function() .  '}';
		}
		
		// Context menu
		if ($widget->get_context_menu_enabled()){
			$grid_head .= ', onRowContextMenu: function(e, index, row) {
					e.preventDefault();
					e.stopPropagation();
					$(this).datagrid("selectRow", index);
	                $("#' . $this->get_id() . '_cmenu").menu("show", {
	                    left: e.pageX,
	                    top: e.pageY
	                });
	                return false;
				}';
		}
		
		if ($this->is_editable()){
			$changes_col_array = array();
			$this->add_on_load_success($this->build_js_edit_mode_enabler());
			// add data and changes getter if the grid is editable
			$output .= "
						function " . $this->build_js_function_prefix() . "getData(){
							var data = [];
							var rows = $('#" . $this->get_id() . "')." . $this->get_element_type() . "('getRows');
							for (var i=0; i<rows.length; i++){
								$('#" . $this->get_id() . "')." . $this->get_element_type() . "('endEdit', i);
								data[$('#" . $this->get_id() . "')." . $this->get_element_type() . "('getRowIndex', rows[i])] = rows[i];
							}
							return data;
						}";
			foreach ($this->get_editors() as $col_id => $editor){
				$col = $widget->get_column($col_id);
				// Skip editors for columns, that are not attributes
				if (!$col->get_attribute()) continue;
				// For all other editors, that belong to related attributes, add some JS to update all rows with that
				// attribute, once the value of one of them changes. This makes sure, that the value of a related attribute
				// is the same, even if it is shown in multiple rows at all times!
				$rel_path = $col->get_attribute()->get_relation_path();
				if ($rel_path && !$rel_path->is_empty()){
					$col_obj_uid = $rel_path->get_relation_last()->get_related_object_key_attribute()->get_alias_with_relation_path();
					$this->add_on_load_success("$('td[field=\'" . $col->get_data_column_name() . "\'] input').change(function(){
						var rows = $('#" . $this->get_id() . "')." . $this->get_element_type() . "('getRows');
						var thisRowIdx = $(this).parents('tr.datagrid-row').attr('datagrid-row-index');
						var thisRowUID = rows[thisRowIdx]['" . $col_obj_uid . "'];
						for (var i=0; i<rows.length; i++){
							if (rows[i]['" . $col_obj_uid . "'] == thisRowUID){
								var ed = $('#" . $this->get_id() . "')." . $this->get_element_type() . "('getEditor', {index: i, field: '" . $col->get_data_column_name() . "'});
								$(ed.target)." . $editor->build_js_value_setter_method("$(this)." . $editor->build_js_value_getter_method()) . ";
							}
						}
					});");
				}
				
				$changes_col_array[] = $widget->get_column($col_id)->get_data_column_name();
			}
			
			$changes_cols = implode("','", $changes_col_array);
			
			if ($changes_cols) $changes_cols = "'" . $changes_cols . "'";
			
			foreach ($widget->get_columns_with_system_attributes() as $col){
				$changes_cols .= ",'" . $col->get_data_column_name() . "'";
			}
			$changes_cols = trim($changes_cols, ',');
			
			$output .= "
						function " . $this->build_js_function_prefix() . "getChanges(){
							var data = [];
							var cols = [" . $changes_cols . "];
							var rowCount = $('#" . $this->get_id() . "')." . $this->get_element_type() . "('getRows').length;
							for (var i=0; i<rowCount; i++){
								$('#" . $this->get_id() . "')." . $this->get_element_type() . "('endEdit', i);
							}
							rows = $('#" . $this->get_id() . "')." . $this->get_element_type() . "('getChanges');
							for (var i=0; i<rows.length; i++){
								$('#" . $this->get_id() . "')." . $this->get_element_type() . "('endEdit', i);
								var row = {};
								for (var j=0; j<cols.length; j++){
									row[cols[j]] = rows[i][cols[j]];
								}
								data.push(row);
							}
							return data;
						}";
		}
		
		// get the standard params for grids and put them before the custom grid head
		$grid_head = $this->build_js_init_options() . $grid_head;
		// Auf manchen Seiten (z.B. Kundenreklamation) kam es nach dem Laden zu Fehlern im Layout
		// (Tabelle nimmt nicht den gesamten verfügbaren Raum ein -> weißer Rand darunter, Spalten-
		// Header sind schmaler als die Inhalte -> verschoben). Durch den Aufruf von "autoSizeColumn"
		// onResize wird das Layout nach dem Laden oder ausklappen der SideBar erneuert. (Auch
		// möglich wäre ein Aufruf von "resize" (dann werden aber die Spaltenbreiten nicht
		// korrigiert) oder "autoSizeColumn" onLoadSuccess ($this->add_on_load_success()) und
		// onLoadError u.U. mit setTimeout()). Durch diese Aenderung wird das Layout leider etwas
		// traeger.
		$resize_function = '';
		if ($widget->has_filters()) {
			$resize_function .= '
					$("#' . $this->get_toolbar_id() . ' .datagrid-filters").masonry({itemSelector: \'.fitem\', columnWidth: ' . $this->get_width_relative_unit() . '});'; 
		}
		$resize_function .= '
					$("#' . $this->get_id() . '").' . $this->get_element_type() . '("autoSizeColumn");';
		$grid_head .= ', fit: true
				, onResize: function(){' . $resize_function . '}'
				. ($this->get_on_change_script() ? ', onSelect: function(index, row){' . $this->get_on_change_script() . '}' : '')
				. ($widget->get_caption() ? ', title: "' . $widget->get_caption() . '"' : '')
				;
		
		// instantiate the data grid
		$output .= '$("#' . $this->get_id() . '").' . $this->get_element_type() . '({' . $grid_head . '});';
			
		// doSearch function for the filters
		$fltrs = array();
		if ($widget->has_filters()){
			foreach($widget->get_filters() as $fnr => $fltr){
				$fltr_impl = $this->get_template()->get_element($fltr, $this->get_page_id());
				$output .= $fltr_impl->generate_js();
				$fltrs[] = '"fltr' . str_pad($fnr, 2, 0, STR_PAD_LEFT) . '_' . urlencode($fltr->get_attribute_alias()) . '": "' . $fltr->get_comparator() . '"+' . $fltr_impl->build_js_value_getter();
			}
		}
		// build JS for the search function
		$output .= '
						function ' . $this->build_js_function_prefix() . 'doSearch(){
							$("#' . $this->get_id() . '").' . $this->get_element_type() . '("load",{' . (count($fltrs)>0 ? implode(', ', $fltrs) . ',' : '') . 'action: "' . $widget->get_lazy_loading_action() . '", resource: "' . $this->get_page_id() . '", element: "' .  $this->get_widget()->get_id() . '"});
						}';
				
		// build JS for the action functions
		foreach ($widget->get_buttons() as $button){
			$output .= $this->get_template()->generate_js($button);
		}
		
		// If the top toolbar is hidden, add actions to the bottom toolbar
		if ($widget->get_hide_toolbar_top() && !$widget->get_hide_toolbar_bottom() && $widget->has_buttons()){
			$bottom_buttons = array();
			foreach ($widget->get_buttons() as $button){
				if ($button->get_action()->get_input_rows_min() == 0){
					$bottom_buttons[] = '{
						iconCls:  "' . $this->build_css_icon_class($button->get_icon_name()) . '",
						title: "' . $button->get_caption() . '",
						handler: ' . $this->get_template()->get_element($button)->build_js_click_function_name() . '
					}'
					;
				}
			}
			
			if (count($bottom_buttons) > 0){
				$output .= '
						
							var pager = $("#' . $this->get_id() . '").datagrid("getPager");
	            			pager.pagination({
								buttons: [' . implode(', ' , $bottom_buttons) . ']
							});
						
					';
			}
		}
		
		return $output;
	}
	
	public function build_js_edit_mode_enabler(){
		return '
					var rows = $(this).' . $this->get_element_type() . '("getRows");
					for (var i=0; i<rows.length; i++){
						$(this).' . $this->get_element_type() . '("beginEdit", i);
					}
				';
	}
	
	/**
	 * The getter will return the value of the UID column of the selected row by default. If the parameter row is
	 * specified, it will return the UID column of that row. Specifying the column parameter will result in returning
	 * the value of that column in the specified row or (if row is not set) the selected row.
	 * IDEA perhaps it should return an entire row as an array if the column is not specified. Just have a feeling, it
	 * might be better...
	 * @see \exface\JEasyUiTemplate\Template\Elements\jeasyuiAbstractWidget::build_js_value_getter()
	 */
	public function build_js_value_getter($column = null, $row = null){
		$output = "$('#" . $this->get_id() . "')";
		if (is_null($row)){
			$output .= "." . $this->get_element_type() . "('getSelected')";
		}
		if (is_null($column)){
			$column = $this->get_widget()->get_meta_object()->get_uid_alias();
		}
		return "(" . $output . " ? " . $output . "['" . $column . "'] : '')";
	}
	
	public function build_js_changes_getter(){
		if ($this->is_editable()){
			$output = $this->build_js_function_prefix() . "getChanges()";
		} else {
			$output = "[]";		
		}
		return $output;
	}
	
	public function build_js_data_getter(ActionInterface $action = null){
		if (is_null($action)){
			$rows = "$('#" . $this->get_id() . "')." . $this->get_element_type() . "('getData')";
		} elseif ($this->is_editable() && $action->implements_interface('iModifyData')){
			if ($this->get_widget()->get_multi_select()){
				$rows = "$('#" . $this->get_id() . "')." . $this->get_element_type() . "('getSelections').length > 0 ? $('#" . $this->get_id() . "')." . $this->get_element_type() . "('getSelections') : " . $this->build_js_function_prefix() . "getChanges()";
			} else {
				$rows = $this->build_js_function_prefix() . "getChanges()";
			}
		} else {
			$rows = "$('#" . $this->get_id() . "')." . $this->get_element_type() . "('getSelections')";
		}
		return "{oId: '" . $this->get_widget()->get_meta_object_id() . "', rows: " . $rows . "}";
	}
	
	public function build_js_refresh(){
		return $this->build_js_function_prefix() . 'doSearch()';
	}
	
	public function generate_headers(){
		$includes = parent::generate_headers();
		// Masonry is neede to align filters nicely
		$includes[] = '<script type="text/javascript" src="exface/vendor/bower-asset/masonry/dist/masonry.pkgd.min.js"></script>';
		// Row details view
		if ($this->get_widget()->has_row_details()){
			$includes[] = '<script type="text/javascript" src="exface/vendor/exface/JEasyUiTemplate/Template/js/jeasyui/extensions/datagridview/datagrid-detailview.js"></script>';
		} 
		/* IDEA The row groups get included always by the current template. Perhaps we need some way to allow manual includes in parallel with automatic ones
		if ($this->get_widget()->has_row_groups()){
			$includes[] = '<script type="text/javascript" src="exface/vendor/exface/JEasyUiTemplate/Template/js/jeasyui/datagridview/datagrid-groupview.js"></script>';
		}*/
		return $includes;
	}
	
	/*
	public function build_js_init_options_head(){
		/* @var $widget exface\Core\Widgets\DataTable */
		/*$widget = $this->get_widget();
		$output = parent::build_js_init_options_head();
		$output .= ', fit: true'
				. ($widget->get_caption() ? ', title: "' . $widget->get_caption() . '"' : '')
				;
		return $output;
	}*/
}
?>