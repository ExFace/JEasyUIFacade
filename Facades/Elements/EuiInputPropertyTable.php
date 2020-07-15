<?php
namespace exface\JEasyUIFacade\Facades\Elements;

class EuiInputPropertyTable extends EuiInput
{

    protected function init()
    {
        parent::init();
        $this->setElementType('propertygrid');
    }

    function buildHtml()
    {
        /* @var $widget \exface\Core\Widgets\InputPropertyTable */
        $widget = $this->getWidget();
        $value = $widget->getValue();
        if (! $value) {
            // TODO Look for default value here
            $value = '{}';
        }
        $output = '	<div class="exf-grid-item ' . $this->getMasonryItemClass() . ' exf-input" title="' . trim($this->buildHintText()) . '" style="width: ' . $this->getWidth() . ';min-width:' . $this->getMinWidth() . ';">
						<textarea name="' . $widget->getAttributeAlias() . '" id="' . $this->getId() . '" style="display:none;" >' . $value . '</textarea>
						<table id="' . $this->buildJsGridId() . '" style="width: 100%; min-height: ' . ($this->getHeightRelativeUnit() * 2) . 'px"></table>
					' . $this->buildHtmlToolbar() . '</div>';
        return $output;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJs()
     */
    function buildJs()
    {
        /* @var $widget \exface\Core\Widgets\InputPropertyTable */
        $widget = $this->getWidget();
        
        $title = str_replace('"', '\"', $widget->getCaption());
        
        // FIXME The ...Sync() JS-method does not really work, because it does not get automatically called after values change. In former times,
        // it got called right before the parent form was submitted. After we stopped using forms, this does not happen anymore. Instead the
        // custom value getter was introduced. The question is, if we still need the textarea and the (now only partially working) synchronisation.
        $output = <<<JS
$(function() {
    $('#{$this->buildJsGridId()}').{$this->getElementType()}({
    	data: JSON.parse($('#{$this->getId()}').val()),
    	showGroup: false,
    	showHeader: false,
    	title: "{$title}",	
    	scrollbarSize: 0,
    	tools: "#{$this->getId()}_tools",	
    	loadFilter: function(input){
    		var data = {"rows":[]};
    		var i=0;
    		for (var key in input){
    			data.rows[i] = {name: key, value: input[key], editor: "text"};
    			i++;
    		}
    		return data;
    	},
    	onLoadSuccess: {$this->buildJsFunctionPrefix()}Sync
    });
});

function {$this->buildJsFunctionPrefix()}Sync(){
	var data = $('#{$this->buildJsGridId()}').propertygrid('getData');
	var result = {};
	for (var i=0; i<data.rows.length; i++){
		$('#{$this->buildJsGridId()}').propertygrid('endEdit', i);
		result[data.rows[i].name] = data.rows[i].value;
		$('#{$this->buildJsGridId()}').propertygrid('beginEdit', i);
	}
	$('#{$this->getId()}').val(JSON.stringify(result));
}

function {$this->buildJsFunctionPrefix()}GetValue(){
	var data = $('#{$this->buildJsGridId()}').propertygrid('getData');
	var result = {};
	for (var i=0; i<data.rows.length; i++){
		$('#{$this->buildJsGridId()}').propertygrid('endEdit', i);
		result[data.rows[i].name] = data.rows[i].value;
		$('#{$this->buildJsGridId()}').propertygrid('beginEdit', i);
	}
	return JSON.stringify(result);
}

{$this->buildJsPropertyAdder()}
{$this->buildJsPropertyRemover()}
JS;
        
        return $output;
    }

    public function buildJsValueGetter()
    {
        return $this->buildJsFunctionPrefix() . 'GetValue()';
    }

    function buildJsInitOptions()
    {
        return '';
    }

    private function buildJsGridId()
    {
        return $this->getId() . '_grid';
    }

    private function hasTools()
    {}

    private function buildHtmlToolbar()
    {
        $output = '';
        /* @var $widget \exface\Core\Widgets\InputPropertyTable */
        $widget = $this->getWidget();
        if ($widget->getAllowAddProperties()) {
            $output .= '<a href="#" class="fa fa-plus" onclick="' . $this->buildJsFunctionPrefix() . 'AddProperties();" title="Append property"></a>';
        }
        if ($widget->getAllowRemoveProperties()) {
            $output .= '<a href="#" class="fa fa-minus" onclick="' . $this->buildJsFunctionPrefix() . 'RemoveProperties();" title="Remove selected properties"></a>';
        }
        if ($output) {
            $output = '<div id="' . $this->getId() . '_tools">' . $output . '</div>';
        }
        return $output;
    }

    private function buildJsPropertyAdder()
    {
        $output = '';
        if ($this->getWidget()->getAllowAddProperties()) {
            $output .= <<<JS
function {$this->buildJsFunctionPrefix()}AddProperties(){
	$.messager.prompt('Add property', 'Please enter property names, separated by commas:', function(r){
		if (r){
			var props = r.split(',');
			for (var i=0; i<props.length; i++){
				$('#{$this->buildJsGridId()}').propertygrid('appendRow',{name: props[i].trim(), value: '', editor: 'text'});
			}
			{$this->buildJsFunctionPrefix()}Sync();
			$('#{$this->buildJsGridId()}').parents('.panel-body').trigger('resize');
		}
	});
}
JS;
        }
        return $output;
    }

    private function buildJsPropertyRemover()
    {
        $output = '';
        if ($this->getWidget()->getAllowRemoveProperties()) {
            $output .= <<<JS
function {$this->buildJsFunctionPrefix()}RemoveProperties(){
	var rows = $('#{$this->buildJsGridId()}').propertygrid('getSelections');
	for (var i=0; i<rows.length; i++){
		$('#{$this->buildJsGridId()}').propertygrid('deleteRow', $('#{$this->buildJsGridId()}').propertygrid('getRowIndex', rows[i]));
	}
	{$this->buildJsFunctionPrefix()}Sync();
	$('#{$this->buildJsGridId()}').parents('.panel-body').trigger('resize');
}
JS;
        }
        return $output;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsValidator()
     */
    function buildJsValidator()
    {
        return 'true';
    }
}