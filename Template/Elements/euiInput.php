<?php
namespace exface\JEasyUiTemplate\Template\Elements;
use exface\Core\Interfaces\Actions\ActionInterface;
use exface\Core\Widgets\Input;
use exface\AbstractAjaxTemplate\Template\Elements\JqueryLiveReferenceTrait;

/**
 * 
 * @method Input get_widget()
 * 
 * @author Andrej Kabachnik
 *
 */
class euiInput extends euiAbstractElement {
	
	use JqueryLiveReferenceTrait;
	
	protected function init(){
		parent::init();
		$this->set_element_type('textbox');
		// If the input's value is bound to another element via an expression, we need to make sure, that other element will
		// change the input's value every time it changes itself. This needs to be done on init() to make sure, the other element
		// has not generated it's JS code yet!
		$this->register_live_reference_at_linked_element();
	}
	
	function generate_html(){
		/* @var $widget \exface\Core\Widgets\Input */
		$widget = $this->get_widget();
		
		$output = '	<input style="height: 100%; width: 100%;"
						name="' . $widget->get_attribute_alias() . '" 
						value="' . $this->get_value_with_defaults() . '" 
						id="' . $this->get_id() . '"  
						' . ($widget->is_required() ? 'required="true" ' : '') . '
						' . ($widget->is_disabled() ? 'disabled="disabled" ' : '') . '
						/>
					';
		return $this->build_html_wrapper_div($output);
	}
	
	public function get_value_with_defaults(){
		if ($this->get_widget()->get_value_expression() && $this->get_widget()->get_value_expression()->is_reference()){
			$value = '';
		} else {
			$value = $this->get_widget()->get_value();
		}
		if (is_null($value) || $value === ''){
			$value = $this->get_widget()->get_default_value();
		} 
		return $this->escape_string($value);
	}
	
	protected function build_html_wrapper_div($html){
		if ($this->get_widget()->get_caption() && !$this->get_widget()->get_hide_caption()){
			$input = '
						<label>' . $this->get_widget()->get_caption() . '</label>
						<div class="exf_input_wrapper">' . $html . '</div>';
		} else {
			$input = $html;
		}
		
		$output = '	<div class="fitem exf_input" title="' . trim($this->build_hint_text()) . '" style="width: ' . $this->get_width() . '; height: ' . $this->get_height() . ';">
						' . $input . '
					</div>';
		return $output;
	}
	
	function generate_js(){
		$output = '';
		$output .= "
				$('#" . $this->get_id() . "')." . $this->get_element_type() . "(" . ($this->build_js_data_options() ? '{' . $this->build_js_data_options() . '}' : '') . ");//.textbox('addClearBtn', 'icon-clear');
				";
		$output .= $this->build_js_live_reference();
		$output .= $this->build_js_on_change_handler();
		return $output;
	}
	
	function build_js_init_options(){
		return $this->build_js_data_options();
	}
	
	protected function build_js_data_options(){
		return '';
	}
	
	function build_js_value_setter_method($value){
		return  $this->get_element_type() . '("setValue", ' . $value . ')';
	}
	
	protected function build_js_on_change_handler(){
		if ($this->get_on_change_script()){
			return "$('#" . $this->get_id() . "').change(function(event){" . $this->get_on_change_script() . "});";
		} else {
			return '';
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\AbstractAjaxTemplate\Template\Elements\AbstractJqueryElement::build_js_data_getter($action, $custom_body_js)
	 */
	public function build_js_data_getter(ActionInterface $action = null){
		if ($this->get_widget()->is_display_only()){
			return '{}';
		} else {
			return parent::build_js_data_getter($action);
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\AbstractAjaxTemplate\Template\Elements\AbstractJqueryElement::build_js_validator()
	 */
	function build_js_validator(){
		$widget = $this->get_widget();
		
		$must_be_validated = !($widget->is_hidden() || $widget->is_readonly() || $widget->is_disabled() || $widget->is_display_only());
		if ($must_be_validated) {
			$output = '$("#' . $this->get_id() . '").' . $this->get_element_type() . '("isValid")';
		} else {
			$output = 'true';
		}
		
		return $output; 
	}
}
?>