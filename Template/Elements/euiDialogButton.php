<?php
namespace exface\JEasyUiTemplate\Template\Elements;
use exface\Core\Interfaces\Actions\ActionInterface;
use exface\AbstractAjaxTemplate\Template\Elements\AbstractJqueryElement;

/**
 * generates jEasyUI-Buttons for ExFace dialogs
 * @author Andrej Kabachnik
 *
 */
class euiDialogButton extends euiButton {
	
	protected function build_js_click_call_server_action(ActionInterface $action, AbstractJqueryElement $input_element){
		// Check if all required attributes are valid before sending the request.
		$widget = $input_element->get_widget();
		
		$output .= '
				if (' . $input_element->build_js_validator() . ') {
					' . parent::build_js_click_call_server_action($action, $input_element) . '
				} else {
					var invalidElements = [];';
		foreach ($widget->get_input_widgets() as $child) {
			if ($child->is_required() && !$child->is_hidden()) {
				$validator = $this->get_template()->get_element($child)->build_js_validator();
				if (!$alias = $child->get_caption()) {
					$alias = method_exists($child, 'get_attribute_alias') ? $child->get_attribute_alias() : $child->get_meta_object()->get_alias_with_namespace();
				}
				$output .= "
					if(!{$validator}) { invalidElements.push('" . $alias . "'); }";
			}
		}
		$output .= '
					' . $this->build_js_show_message_error('"' . $this->translate('MESSAGE.FILL_REQUIRED_ATTRIBUTES') . '" + invalidElements.join(", ")') . '
				}';
		
		return $output;
	}
}
?>