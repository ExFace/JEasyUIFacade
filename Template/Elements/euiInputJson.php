<?php
namespace exface\JEasyUiTemplate\Template\Elements;
class euiInputJson extends euiInputText {
	
	protected function init(){
		parent::init();
		$this->set_element_type('div');
		$this->set_height_default(5);
	}
	
	function generate_html(){
		$output = ' <input type="hidden"
							name="' . $this->get_widget()->get_attribute_alias() . '"
							id="' . $this->get_id() . '">
					<div id="' . $this->get_id() . '_editor" style="height: 100%; width: 100%;"></div>';
		return $this->build_html_wrapper_div($output);
	}
	
	function generate_js(){
		$init_value = $this->get_widget()->get_value() ? 'editor.set(' . $this->get_widget()->get_value() . ');' : '';
		$script = <<<JS
	var container = document.getElementById("{$this->get_id()}_editor");
    var editor = new JSONEditor(container, 
    				{
    					mode: 'tree',
   						modes: ['code', 'form', 'text', 'tree', 'view'],
   						change: function(){ $('#{$this->get_id()}').val(editor.getText()); }
					}
    	);
    {$init_value}
    editor.expandAll();
    $(container).parents('.exf_input').children('label').css('vertical-align', 'top');
	$('#{$this->get_id()}').val(editor.getText());
JS;
		return $script;
	}
	
	public function generate_headers(){
		$includes = parent::generate_headers();
		$includes[] = '<link href="exface/vendor/bower-asset/jsoneditor/dist/jsoneditor.min.css" rel="stylesheet">';
		$includes[] = '<script type="text/javascript" src="exface/vendor/bower-asset/jsoneditor/dist/jsoneditor.min.js"></script>';
		return $includes;
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