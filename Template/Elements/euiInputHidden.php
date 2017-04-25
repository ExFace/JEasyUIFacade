<?php
namespace exface\JEasyUiTemplate\Template\Elements;
class euiInputHidden extends euiInput {
	
	protected function init(){
		parent::init();
		$this->set_element_type('hidden');
	}
	
	function generate_html(){
		$output = '<input type="hidden" 
								name="' . $this->get_widget()->get_attribute_alias() . '" 
								value="' . $this->get_value_with_defaults() . '" 
								id="' . $this->get_id() . '" />';
		return $output;
	}
	
	function generate_js(){
		$output .= $this->build_js_live_reference();
		return $output;
	}
	
	function build_js_value_setter_method($value){
		return  'val(' . $value . ')';
	}
	
	function build_js_value_getter_method(){
		return  'val()';
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