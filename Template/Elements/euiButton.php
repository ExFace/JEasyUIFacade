<?php
namespace exface\JEasyUiTemplate\Template\Elements;

use exface\Core\Widgets\DialogButton;
use exface\Core\Interfaces\Actions\ActionInterface;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryButtonTrait;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryAlignmentTrait;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement;
use exface\Core\Widgets\Dialog;
use exface\Core\Widgets\Button;
use exface\Core\Widgets\ButtonGroup;

/**
 * generates jEasyUI-Buttons for ExFace
 * 
 * @method Button getWidget()
 * @method euiAbstractElement getInputElement()
 *
 * @author Andrej Kabachnik
 *        
 */
class euiButton extends euiAbstractElement
{
    
    use JqueryButtonTrait;
    use JqueryAlignmentTrait;

    function buildJs()
    {
        $output = '';
        $action = $this->getAction();
        
        // Generate helper functions, that do not depend on the action
        
        // Get the click function for the button. This might also be required for buttons without actions
        if ($click = $this->buildJsClickFunction()) {
            // Generate the function to be called, when the button is clicked
            $output .= "
				function " . $this->buildJsClickFunctionName() . "(){
					" . $click . "
				}";
        }
        
        // Get the java script required for the action itself
        if ($action) {
            // Actions with template scripts may contain some helper functions or global variables.
            // Print the here first.
            if ($action && $action->implementsInterface('iRunTemplateScript')) {
                $output .= $this->getAction()->buildScriptHelperFunctions();
            }
        }
        
        return $output;
    }

    /**
     *
     * @see \exface\JEasyUiTemplate\Template\Elements\abstractWidget::buildHtml()
     */
    function buildHtml()
    {
        // Create a linkbutton
        $output .= $this->buildHtmlButton();
        
        return $output;
    }

    public function buildHtmlButton()
    {
        $widget = $this->getWidget();
        
        $style = '';
        if (! $widget->getParent() instanceof ButtonGroup){
            // TODO look for the default alignment for buttons for the input
            // widget in the config of this template
            switch ($this->buildCssTextAlignValue($widget->getAlign())) {
                case 'left':
                    break;
                case 'right':
                    $style .= 'float: right;';
                    break;
            }
        }
        
        $output = '
				<a id="' . $this->getId() . '" title="' . str_replace('"', '\"', $widget->getHint()) . '" href="#" class="easyui-linkbutton" data-options="' . $this->buildJsDataOptions() . '" style="' . $style . '" onclick="' . $this->buildJsFunctionPrefix() . 'click();">
						' . $widget->getCaption() . '
				</a>';
        return $output;
    }
    
    protected function buildJsDataOptions()
    {
        $widget = $this->getWidget();
        $data_options = '';
        if ($widget->getVisibility() != EXF_WIDGET_VISIBILITY_PROMOTED) {
            $data_options .= 'plain: true';
        } else {
            $data_options .= 'plain: false';
        }
        if ($widget->isDisabled()) {
            $data_options .= ', disabled: true, plain: true';
        }
        if ($widget->getIcon() && !$widget->getHideButtonIcon()) {
            $data_options .= ", iconCls: '" . $this->buildCssIconClass($widget->getIcon()) . "'";
        }
        return $data_options;
    }

    protected function buildJsClickShowDialog(ActionInterface $action, AbstractJqueryElement $input_element)
    {
        $widget = $this->getWidget();
        
        /* @var $prefill_link \exface\Core\CommonLogic\WidgetLink */
        $prefill = '';
        if ($prefill_link = $this->getAction()->getPrefillWithDataFromWidgetLink()) {
            if ($widget->getPage()->is($prefill_link->getPageAlias())) {
                $prefill = ", prefill: " . $this->getTemplate()->getElement($prefill_link->getWidget())->buildJsDataGetter($this->getAction());
            }
        }
        
        $output = $this->buildJsRequestDataCollector($action, $input_element);
        $output .= <<<JS
						{$this->buildJsBusyIconShow()}
						$.ajax({
							type: 'POST',
							url: '{$this->getAjaxUrl()}',
							dataType: 'html',
							data: {
								action: '{$widget->getActionAlias()}',
								resource: '{$widget->getPage()->getAliasWithNamespace()}',
								element: '{$widget->getId()}',
								data: requestData
								{$prefill}
							},
							success: function(data, textStatus, jqXHR) {
								{$this->buildJsCloseDialog($widget, $input_element)}
		                       	if ($('#ajax-dialogs').length < 1){
		                       		$('body').append('<div id=\"ajax-dialogs\"></div>');
                       			}
								$('#ajax-dialogs').append('<div class=\"ajax-wrapper\">'+data+'</div>');
								var dialogId = $('#ajax-dialogs').children().last().children('.easyui-dialog').attr('id');
		                       	$.parser.parse($('#ajax-dialogs').children().last());
								var onCloseFunc = $('#'+dialogId).panel('options').onClose;
								$('#'+dialogId).panel('options').onClose = function(){
									onCloseFunc();
									
									// MenuButtons manuell zerstoeren, um Ueberbleibsel im body zu verhindern
									var menubuttons = $('#'+dialogId).parent().find('.easyui-menubutton');
									for (i = 0; i < menubuttons.length; i++) {
										$(menubuttons[i]).menubutton('destroy');
									}
									
									$(this).dialog('destroy').remove(); 
									$('#ajax-dialogs').children().last().remove();
									{$this->buildJsInputRefresh($widget, $input_element)}
								};
                       			$(document).trigger('{$action->getAliasWithNamespace()}.action.performed', [requestData]);
                       			{$this->buildJsBusyIconHide()}
							},
							error: function(jqXHR, textStatus, errorThrown){
								{$this->buildJsShowError('jqXHR.responseText', 'jqXHR.status + " " + jqXHR.statusText')}
								{$this->buildJsBusyIconHide()}
							}
						});
						{$this->buildJsCloseDialog($widget, $input_element)} 
JS;
        return $output;
    }

    protected function buildJsCloseDialog($widget, $input_element)
    {
        if ($widget instanceof DialogButton && $widget->getCloseDialogAfterActionSucceeds()){
            if ($widget->getInputWidget() instanceof Dialog){
                return "$('#" . $input_element->getId() . "').dialog('close');";
            } else {
                // IDEA close the closest parent dialog here? This maybe usefull
                // if the dialog button has a custom input widget - not a dialog.
            }
        }
        return "";
    }

    /**
     * In jEasyUI the button does not need any extra headers, as all headers needed for whatever the button loads will
     * come with the AJAX-request.
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildHtmlHeadTags()
     */
    public function buildHtmlHeadTags()
    {
        return array();
    }
}
?>