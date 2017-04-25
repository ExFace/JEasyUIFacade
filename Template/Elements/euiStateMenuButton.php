<?php
namespace exface\JEasyUiTemplate\Template\Elements;

/**
 * 
 * @author SFL
 *
 */
class euiStateMenuButton extends euiMenuButton {
	
	/**
	 * @see \exface\Templates\jeasyui\Widgets\abstractWidget::generate_html()
	 */
	function generate_html(){
		$widget = $this->get_widget();
		$button_no = count($widget->get_buttons());
		$output = '';
	
		if ($button_no == 1) {
			/* @var $b \exface\Core\Widgets\Button */
			$b = $widget->get_buttons()[0];
			$b->set_caption($widget->get_caption());
			$b->set_align($widget->get_align());
			$b->set_visibility($widget->get_visibility());
			$output = $this->get_template()->get_element($b)->generate_html();
				
		} elseif ($button_no > 1) {
			$output = parent::generate_html();
		}
	
		return $output;
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \exface\JEasyUiTemplate\Template\Elements\euiMenuButton::generate_js()
	 */
	function generate_js(){
		$widget = $this->get_widget();
		$button_no = count($widget->get_buttons());
		$output = '';
		
		if ($button_no == 1) {
			$output = $this->get_template()->get_element($widget->get_buttons()[0])->generate_js();
	
		} elseif ($button_no > 1) {
			$output = parent::generate_js();
		}
		
		return $output;
	}
}
?>
