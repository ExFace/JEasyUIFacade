<?php
namespace exface\JEasyUiTemplate\Template\Elements;
class euiInputPropertyTable extends euiInput {
	
	function init(){
		$this->set_element_type('propertygrid');
	}
	
	function generate_html(){
		/* @var $widget \exface\Core\Widgets\InputPropertyTable */
		$widget = $this->get_widget();
		$value = $widget->get_value();
		if (!$value){
			// TODO Look for default value here
			$value = '{}';
		}
		$output = '	<div class="fitem exf_input" title="' . trim($this->get_hint()) . '" style="width: ' . $this->get_width() . '">
						<textarea name="' . $widget->get_attribute_alias() . '" id="' . $this->get_id() . '" style="display:none;" >' . $value . '</textarea>
						<table id="' . $this->build_js_grid_id() . '" width="100%"></table>
					'  . $this->build_html_toolbar() . '</div>';
		return $output;
	}
	
	function generate_js(){
		/* @var $widget \exface\Core\Widgets\InputPropertyTable */
		$widget = $this->get_widget();
		
		$output = <<<JS

$('#{$this->build_js_grid_id()}').{$this->get_element_type()}({
	data: JSON.parse($('#{$this->get_id()}').val()),
	showGroup: false,
	showHeader: false,
	title: "{$widget->get_caption()}",	
	scrollbarSize: 0,
	tools: "#{$this->get_id()}_tools",	
	loadFilter: function(input){
		var data = {"rows":[]};
		var i=0;
		for (var key in input){
			data.rows[i] = {name: key, value: input[key], editor: "text"};
			i++;
		}
		return data;
	},
	onLoadSuccess: {$this->get_function_prefix()}Sync
});
function {$this->get_function_prefix()}Sync(){
	var data = $('#{$this->build_js_grid_id()}').propertygrid('getData');
	var result = {};
	for (var i=0; i<data.rows.length; i++){
		$('#{$this->build_js_grid_id()}').propertygrid('endEdit', i);
		result[data.rows[i].name] = data.rows[i].value;
		$('#{$this->build_js_grid_id()}').propertygrid('beginEdit', i);
	}
	$('#{$this->get_id()}').val(JSON.stringify(result));
}
$('#{$this->build_js_grid_id()}').parents('form').form({onSubmit: {$this->get_function_prefix()}Sync});
{$this->build_js_property_adder()}
{$this->build_js_property_remover()}
JS;

		return $output;
	}
	
	function build_js_init_options(){
		return '';
	}
	
	private function build_js_grid_id(){
		return $this->get_id() . '_grid';
	}
	
	private function has_tools(){
		
	}
	
	private function build_html_toolbar(){
		$output = '';
		/* @var $widget \exface\Core\Widgets\InputPropertyTable */
		$widget = $this->get_widget();
		if ($widget->get_allow_add_properties()){
			$output .= '<a href="#" class="icon-add" onclick="' . $this->get_function_prefix() . 'AddProperties();" title="Append property"></a>';
		}
		if ($widget->get_allow_remove_properties()){
			$output .= '<a href="#" class="icon-remove" onclick="' . $this->get_function_prefix() . 'RemoveProperties();" title="Remove selected properties"></a>';
		}
		if ($output){
			$output = '<div id="' . $this->get_id() . '_tools">' . $output . '</div>';
		}
		return $output;
	}
	
	private function build_js_property_adder(){
		$output = '';
		if ($this->get_widget()->get_allow_add_properties()){
			$output .= <<<JS
function {$this->get_function_prefix()}AddProperties(){
	$.messager.prompt('Add property', 'Please enter property names, separated by commas:', function(r){
		if (r){
			var props = r.split(',');
			for (var i=0; i<props.length; i++){
				$('#{$this->build_js_grid_id()}').propertygrid('appendRow',{name: props[i].trim(), value: '', editor: 'text'});
			}
			{$this->get_function_prefix()}Sync();
			$('#{$this->build_js_grid_id()}').parents('.panel-body').trigger('resize');
		}
	});
}
JS;
		}
		return $output;
	}
	
	private function build_js_property_remover(){
		$output = '';
		if ($this->get_widget()->get_allow_remove_properties()){
			$output .= <<<JS
function {$this->get_function_prefix()}RemoveProperties(){
	var rows = $('#{$this->build_js_grid_id()}').propertygrid('getSelections');
	for (var i=0; i<rows.length; i++){
		$('#{$this->build_js_grid_id()}').propertygrid('deleteRow', $('#{$this->build_js_grid_id()}').propertygrid('getRowIndex', rows[i]));
	}
	{$this->get_function_prefix()}Sync();
	$('#{$this->build_js_grid_id()}').parents('.panel-body').trigger('resize');
}
JS;
		}
		return $output;
	}
}