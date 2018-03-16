<?php
namespace exface\JEasyUiTemplate\Template\Elements;

use exface\Core\Widgets\Planogram;
use exface\Core\CommonLogic\Model\RelationPath;

class euiPlanogram extends euiDiagram
{

    public function buildHtml()
    {
        $button_html = "";
        foreach ($this->getWidget()->getShapes() as $shape) {
            
            $button_html .= $this->getTemplate()->getElement($shape->getData())->buildHtmlButtons() . "\n";
            $menu_html .= $this->getTemplate()->getElement($shape->getData())->buildHtmlContextMenu();
            
            // Create a context menu if any items were found
            if (count($shape->getData()->getButtons()) > 1 && $menu_html) {
                $menu_html = '<div id="' . $this->getId() . '_smenu" class="easyui-menu">' . $menu_html . '</div>';
            } else {
                $menu_html = '';
            }
        }
        $output = <<<HTML

<div id="{$this->getId()}_panel" class="easyui-panel" title="{$this->getCaption()}" style="" data-options="fit:true,tools:'#{$this->getId()}_tools',onResize:function(){ if ($('#{$this->getId()} svg').length > 0) { $('#{$this->getId()}').planogram({width: $(this).width(), height: $(this).height()})}}">
	{$this->getTemplate()->getElement($this->getWidget()->getDiagramObjectSelectorWidget())->buildHtml()}
    <div id="{$this->getId()}" style="margin: 10px 3px 0 3px; text-align: center;">

    </div>
	<div id="{$this->getId()}_tools">
		<a href="319.html" class="fa fa-external-link" title="Preview" target="_blank"></a>
		<a href="#" onclick="{$this->buildJsRefresh()};" class="fa fa-refresh" title="{$this->getTemplate()->getApp()->getTranslator()->translate('REFRESH')}"></a>
	</div>
	<div style="display:none">
		{$menu_html}
		{$button_html}
	</div>
</div>
				
		
HTML;
        return $output;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::getWidget()
     * @return Planogram
     */
    public function getWidget()
    {
        return parent::getWidget();
    }

    public function buildJs()
    {
        $widget = $this->getWidget();
        $actions_js = '';
        foreach ($widget->getShapes() as $shape) {
            // TODO currently just rendering the last shape
            
            // Shape data to display within each shape
            $data_display_rows = array();
            foreach ($shape->getData()->getColumns() as $column) {
                if ($column->isHidden())
                    continue;
                $data_display_rows[] = "[{type:'param', val:'" . $column->getDataColumnName() . "'}]";
            }
            $data_display = implode(',', $data_display_rows);
            
            // Shape data buttons for click-menus
            /* @var $button \exface\Core\Widgets\Button */
            /* @var $button_element \exface\JEasyUiTemplate\Template\Elements\euiButton */
            foreach ($shape->getData()->getButtons() as $button) {
                $button_element = $this->getTemplate()->getElement($button);
                $actions_js .= $button_element->buildJs() . "\n";
                $shape_click_js = $button_element->buildJsClickFunctionName() . '();';
            }
            
            if (count($shape->getData()->getButtons()) > 1) {
                $shape_click_js = '$("#' . $this->getId() . '_smenu").menu("show", {
	                    left: e.pageX,
	                    top: e.pageY
	                });';
            }
        }
        
        /* @var $relation_to_diagram \exface\Core\CommonLogic\Model\RelationPath */
        $relation_to_diagram = $shape->getRelationPathToDiagramObject();
        $relation_from_data_to_diagram = $shape->getData()->getMetaObject()->findRelationPath($widget->getMetaObject())->toString();
        $filter_shape_options = 'data.' . $this->getTemplate()->getUrlFilterPrefix() . RelationPath::relationPathAdd($relation_to_diagram->toString(), $relation_to_diagram->getEndObject()->getUidAttributeAlias()) . ' = ' . $this->getTemplate()->getElement($widget->getDiagramObjectSelectorWidget())->buildJsValueGetter() . ';';
        $filter_shape_data = 'data.' . $this->getTemplate()->getUrlFilterPrefix() . $relation_from_data_to_diagram . ' = ' . $this->getTemplate()->getElement($widget->getDiagramObjectSelectorWidget())->buildJsValueGetter() . ';';
        
        $bg_image = $widget->getPrefillData()->getCellValue($widget->getBackgroundImageAttributeAlias(), 0);
        if ($bg_image) {
            $bg_image_size = getimagesize($widget->getWorkbench()->filemanager()->getPathToBaseFolder() . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $bg_image);
        } else {
            $bg_image_size = array(
                0,
                0
            );
        }
        if ($bg_image_size[1] > 800) {
            $width = "100%";
            $height = "auto";
        } else {
            $width = "auto";
            $height = "90%";
        }
        
        if ($widget->getAddRowLinkButtonId()) {
            $add_row_function = $this->getTemplate()->getElementByWidgetId($widget->getAddRowLinkButtonId(), $widget->getPage())->buildJsClickFunction();
            $add_row_function = preg_replace([
                '/var requestData = {.*?};\r?\n/',
                '/, prefill: {.*?}\r?\n/'
            ], [
                '',
                ''
            ], $add_row_function);
            $vm_shelf_oid = $this->getTemplate()->getElement($this->getWidget()->getDiagramObjectSelectorWidget())->buildJsValueGetter();
        }
        
        $ajax_headers = ! empty($this->getAjaxHeaders()) ? 'headers: ' . json_encode($this->getAjaxHeaders()) . ',' : '';
        
        $output = <<<JS

$(document).ready(function(){
	var {$this->getId()}planogram = $("#{$this->getId()}").planogram({
    	background: '{$bg_image}',
    	backgroundStretch: 'fit',
		shapeLoader: {$this->buildJsFunctionPrefix()}shapeLoader,
		dataLoader: {$this->buildJsFunctionPrefix()}dataLoader,
		boxWidth: {$bg_image_size[0]},
		boxHeight: {$bg_image_size[1]},
		width: "{$width}",
		height: "{$height}",
		parentElement: $("#{$this->getId()}").parentsUntil(".panel", ".panel-body"),
		onLoad: function(){ {$this->buildJsBusyIconHide()} },
		onShapeClick: function(data){
			{$this->getId()}_selected = $(this).parent();
        	{$shape_click_js}
		},
		onDrop: function(plugin, dragItem, dropArea){
            var draggableItemShelf = $(dragItem).attr("data-shelf-oid");
            var draggableOID = $(dragItem).attr("data-oid");
            var enteredItemShelf = $(dropArea).attr("data-oid");

            if (draggableItemShelf == enteredItemShelf) {
                resetElement(dragItem);
                console.log("Dropped in same shelf - nothing is accomplished");
                return;
            } else {
                console.log("Element with ID " + draggableOID + " from Shelf " + draggableItemShelf + " was dropped in Shelf " + enteredItemShelf);
                if ($(dragItem).is('.dragRow')) {
                	var requestData = {oId: '0x11e6b0c8227136b78943e4b318306b9a', rows: [{ARTICLE_COLOR: draggableOID, VM_SHELF: {$vm_shelf_oid}, VM_SHELF_MODEL_POSITION: enteredItemShelf}]};
        			{$add_row_function}
				}
                return;
            }
        },
		shapeOptionsDefaults: {
            style: {'shape-fill': 'rgba(184,229,229,0.6)',
                    'shape-stroke-width': 1,
                    'shape-stroke':'rgb(121,205,205)',
                    'text-fill':'rgb(0,0,0)',
                    'text-stroke-width': 0,
                    'text-font-family': 'Arial',
                    'text-font-size'   : 12,
                    },
            titleBoxOffset: [4,4,"bottomright"],             //negative offset for area name [x,y,position]
            id: '{$widget->getShapes()[0]->getMetaObject()->getUidAttributeAlias()}',
            label: '{$widget->getShapes()[0]->getShapeCaptionAttributeAlias()}',
            options: '{$widget->getShapes()[0]->getShapeOptionsAttributeAlias()}'
        },
		dataTextField: [
           {$data_display}
        ]
	});

	$("body").on('click', '#VisualPlaceholder svg polygon', function(){
        alert("My name is "+$(this).data("oid"));
    });
    
    $("body").on('click', '#{$this->getId()} svg text', function(e){
   		{$this->getId()}_selected = $(this).parent();
        {$shape_click_js}
    });
    
    interact('tr.datagrid-row').draggables({max: 2});
});
		
function {$this->buildJsFunctionPrefix()}shapeLoader(){
	{$this->buildJsBusyIconShow()}
	var data = {};
	var diagram = this;
	data.resource = "{$widget->getPage()->getAliasWithNamespace()}";
	data.element = "{$shape->getId()}";
	data.object = "{$shape->getMetaObject()->getId()}";
	data.action = "{$widget->getLazyLoadingActionAlias()}";
	{$filter_shape_options}
	
	$.ajax({
		type: "POST",
		url: "{$this->getAjaxUrl()}",
        {$ajax_headers}
		data: data,
		success: function(data){
			diagram.setAreaData(data['rows']);
		},
		dataType: "json"
	});
}
		
function {$this->buildJsFunctionPrefix()}dataLoader(){
	{$this->buildJsBusyIconShow()}
	var data = {};
	var result = [];
	var diagram = this;
	data.resource = "{$widget->getPage()->getAliasWithNamespace()}";
	data.element = "{$shape->getData()->getId()}";
	data.object = "{$shape->getData()->getMetaObject()->getId()}";
	data.action = "{$shape->getData()->getLazyLoadingActionAlias()}";
	{$filter_shape_data}
	
	$.ajax({
		type: "POST",
		url: "{$this->getAjaxUrl()}",
        {$ajax_headers}
		data: data,
		success: function(data){
			diagram.setElementData(data['rows']);
		},
		dataType: "json"
	});
}
		
{$actions_js}
JS;
        return $output . parent::buildJs();
    }

    public function buildJsRefresh()
    {
        return "$('#" . $this->getId() . "').planogram('refreshData');";
    }

    public function buildHtmlHeadTags()
    {
        $includes = parent::buildHtmlHeadTags();
        // $includes[] = '<link rel="stylesheet" media="screen" href="exface/vendor/exface/jEasyUiTemplate/Template/js/planogram/style.css">';
        $includes[] = '<script type="text/javascript" src="exface/vendor/exface/jEasyUiTemplate/Template/js/planogram/planogram.plugin.js"></script>';
        $includes[] = '<script type="text/javascript" src="exface/vendor/exface/jEasyUiTemplate/Template/js/planogram/interact.js"></script>';
        return $includes;
    }

    public function buildJsBusyIconShow()
    {
        return 'if($("#' . $this->getId() . ' .datagrid-mask").length == 0) $("#' . $this->getId() . '").append(\'<div class="datagrid-mask" style="display:block"></div><div class="datagrid-mask-msg" style="display: block; left: 50%; height: 40px; margin-left: -90px; line-height: 40px;">Processing, please wait ...</div>\');';
    }

    public function buildJsBusyIconHide()
    {
        return '$("#' . $this->getId() . ' .datagrid-mask").remove();$("#' . $this->getId() . ' .datagrid-mask-msg").remove();';
    }
    
    protected function buildHtmlContextMenu()
    {
        $widget = $this->getWidget();
        $context_menu_html = '';
        if ($widget->hasButtons()) {
            foreach ($widget->getToolbarMain()->getButtonGroupFirst()->getButtons() as $button) {
                $context_menu_html .= str_replace(['<a id="', '</a>', 'easyui-linkbutton'], ['<div id="' . $this->getId() . '_', '</div>', ''], $this->getTemplate()->getElement($button)->buildHtmlButton());
            }
            
            foreach ($widget->getToolbars() as $toolbar){
                foreach ($toolbar->getButtonGroups() as $btn_group){
                    if ($btn_group !== $widget->getToolbarMain()->getButtonGroupFirst() && $btn_group->hasButtons()){
                        $context_menu_html .= '<div class="menu-sep"></div>';
                        foreach ($btn_group->getButtons() as $button){
                            $context_menu_html .= str_replace(['<a id="', '</a>', 'easyui-linkbutton'], ['<div id="' . $this->getId() . '_', '</div>', ''], $this->getTemplate()->getElement($button)->buildHtmlButton());
                        }
                    }
                }
            }
        }
        return $context_menu_html;
    }
}
?>