<?php namespace exface\JEasyUiTemplate\Template;

use exface\AbstractAjaxTemplate\Template\AbstractAjaxTemplate;
use exface\Core\Exceptions\DependencyNotFoundError;

class JEasyUiTemplate extends AbstractAjaxTemplate {
	
	public function init(){
		parent::init();
		$this->set_class_prefix('eui');
		$this->set_class_namespace(__NAMESPACE__);
		$this->set_request_system_vars(array('_'));
		$folder = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'jeasyui';
		if (!is_dir($folder)){
			throw new DependencyNotFoundError('jEasyUI files not found! Please install jEasyUI to "' . $folder . '"!', '6T6HUFO');
		}
	}
	
	public function get_request_filters(){
		parent::get_request_filters();
		// id is a special filter for dynamic tree loading in jeasyui
		if ($this->get_workbench()->get_request_params()['id']){
			$this->request_filters_array['PARENT'][] = urldecode($this->get_workbench()->get_request_params()['id']);
			$this->get_workbench()->remove_request_param('id');
		}
		return $this->request_filters_array;
	}
}
?>