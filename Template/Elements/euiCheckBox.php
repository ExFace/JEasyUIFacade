<?php
namespace exface\JEasyUiTemplate\Template\Elements;
class euiCheckBox extends euiInput {
	
	protected function init(){
		$this->set_element_type('checkbox');
	}
	
	function generate_html(){
		$output = '	<div style="width: calc(100% + 2px); height: 100%; display: inline-block; text-align:left;">
						<input type="checkbox" value="1" 
								id="' . $this->get_id() . '_checkbox"
								onchange="$(\'#' . $this->get_id() . '\').val(this.checked);"' . '
								' . ($this->get_value_with_defaults() ? 'checked="checked" ' : '') . '
								' . ($this->get_widget()->is_disabled() ? 'disabled="disabled"' : '') . ' />
						<input type="hidden" name="' . $this->get_widget()->get_attribute_alias() . '" id="' . $this->get_id() . '" value="' . $this->get_value_with_defaults() . '" />
					</div>';
		return $this->build_html_wrapper_div($output);
	}
	
	function generate_js(){
		return '';
	}
	
	function build_js_value_getter(){
		return '$("#' . $this->get_id() . '_checkbox").' . $this->build_js_value_getter_method();
	}
	
	function build_js_value_getter_method(){
		return 'prop(\'checked\')';
	}
	
	function build_js_value_setter($value){
		return '($("#' . $this->get_id() . '_checkbox").' . $this->build_js_value_setter_method($value);
	}
	
	function build_js_value_setter_method($value){
		return 'prop(\'checked\', ' . $value . ')';
	}
	
	function build_js_init_options(){
		$options = 'on: "1"'
				. ', off: "0"'
				. ($this->get_widget()->is_disabled() ? ', disabled: true' : '');
		return $options;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\AbstractAjaxTemplate\Template\Elements\AbstractJqueryElement::build_js_validator()
	 */
	function build_js_validator(){
		return 'true';
	}
}
?>