<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\DialogButton;
use exface\Core\Interfaces\Actions\ActionInterface;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryButtonTrait;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryAlignmentTrait;
use exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement;
use exface\Core\Widgets\Dialog;
use exface\Core\Widgets\Button;
use exface\Core\Widgets\ButtonGroup;
use exface\Core\Interfaces\Actions\iShowDialog;

/**
 * Generates jEasyUI linkbutton controls for Button widgets
 * 
 * @method Button getWidget()
 * @method EuiAbstractElement getInputElement()
 *
 * @author Andrej Kabachnik
 *        
 */
class EuiButton extends EuiAbstractElement
{
    use JqueryButtonTrait;
    use JqueryAlignmentTrait;
    
    protected function init()
    {
        parent::init();
        
        // Register an onChange-Script on the element linked by a disable condition.
        $this->registerDisableConditionAtLinkedElement();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::getElementType()
     */
    public function getElementType() : ?string
    {
        return 'linkbutton';
    }

    public function buildJs()
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
            // Actions with facade scripts may contain some helper functions or global variables.
            // Print the here first.
            if ($action && $action->implementsInterface('iRunFacadeScript')) {
                $output .= $this->getAction()->buildScriptHelperFunctions($this->getFacade());
            }
        }
        
        // Initialize the disabled state of the widget if a disabled condition is set.
        $output .= $this->buildJsDisableConditionInitializer();
        
        if ((null !== $action = $this->getAction()) && $action instanceof iShowDialog && $action->isDialogLazyLoading(true) === false) {
            $output .= <<<JS

            {$this->getFacade()->getElement($action->getDialogWidget())->buildJs()}

JS;
        }
        
        return $output;
    }

    /**
     *
     * @see \exface\JEasyUIFacade\Facades\Elements\abstractWidget::buildHtml()
     */
    function buildHtml()
    {
        // Create a linkbutton
        $output .= $this->buildHtmlButton();
        
        if ((null !== $action = $this->getAction()) && $action instanceof iShowDialog && $action->isDialogLazyLoading(true) === false) {
            $output .= <<<HTML

            <div style="display:none" id="{$this->getId()}_DialogWrapper">
                {$this->getFacade()->getElement($action->getDialogWidget())->buildHtml()};
            </div>

HTML;
        }
        
        return $output;
    }

    /**
     * 
     * @return string
     */
    public function buildHtmlButton()
    {
        $widget = $this->getWidget();
        
        $style = '';
        if (! $widget->getParent() instanceof ButtonGroup){
            // TODO look for the default alignment for buttons for the input
            // widget in the config of this facade
            switch ($this->buildCssTextAlignValue($widget->getAlign())) {
                case 'left':
                    break;
                case 'right':
                    $style .= 'float: right;';
                    break;
            }
        }
        
        $output = '
				<a id="' . $this->getId() . '" title="' . str_replace('"', '\"', $widget->getHint()) . '" href="#" class="easyui-' . $this->getElementType() . '" data-options="' . $this->buildJsDataOptions() . '" style="' . $style . '" onclick="' . $this->buildJsFunctionPrefix() . 'click();">
						' . $this->getCaption() . '
				</a>';
        return $output;
    }
    
    protected function buildJsDataOptions()
    {
        $widget = $this->getWidget();
        $data_options = '';
        if ($widget->getVisibility() !== EXF_WIDGET_VISIBILITY_PROMOTED) {
            $data_options .= 'plain: true';
        } else {
            $data_options .= 'plain: false';
        }
        if ($widget->isDisabled()) {
            $data_options .= ', disabled: true, plain: true';
        }
        
        $showIconByDefault = $widget->getAppearance() !== Button::APPEARANCE_LINK;
        if ($widget->getIcon() && $widget->getShowIcon($showIconByDefault)) {
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
            if ($prefill_link->getTargetPageAlias() === null || $widget->getPage()->is($prefill_link->getTargetPage())) {
                $prefill = ", prefill: " . $this->getFacade()->getElement($prefill_link->getTargetWidget())->buildJsDataGetter($this->getAction());
            }
        }
        
        $headers = ! empty($this->getAjaxHeaders()) ? 'headers: ' . json_encode($this->getAjaxHeaders()) . ',' : '';
        
        $output = $this->buildJsRequestDataCollector($action, $input_element);
        if ($action instanceof iShowDialog && $action->isDialogLazyLoading(true) === false) {
            if ($widget instanceof DialogButton) {                
                $output .= <<<JS
                
                        (function(){
                            var onCloseFunc = $('#{$this->getInputElement()->getId()}').panel('options').onClose;
    						$('#{$this->getInputElement()->getId()}').panel('options').onClose = function(){
    							//$('#{$this->getFacade()->getElement($action->getDialogWidget())->getId()}').dialog('destroy');
                                $('#{$this->getFacade()->getElement($action->getDialogWidget())->getId()}').remove();
                                onCloseFunc();
    						};
                        })();
                        
JS;
            }
            $output .= <<<JS
            
                        {$this->buildJsCloseDialog($widget, $input_element)}
                        
                        $('#{$this->getFacade()->getElement($action->getDialogWidget())->getId()}').dialog('open');
                        
                        (function(){
                            var onCloseFunc = $('#{$this->getFacade()->getElement($action->getDialogWidget())->getId()}').panel('options').onClose;
    						$('#{$this->getFacade()->getElement($action->getDialogWidget())->getId()}').panel('options').onClose = function(){
    							onCloseFunc();
                                {$this->buildJsTriggerActionEffects($action)}
    						};
                        })();
								
JS;
        } else {
            // NOTE: trigger action effects AFTER removing the closed dialog - otherwise
            // this might cause refreshes on the dialog tables that are totally useless!
            $output .= <<<JS
						{$this->buildJsBusyIconShow()}
						$.ajax({
							type: 'POST',
							url: '{$this->getAjaxUrl()}',
                            {$headers}
							dataType: 'html',
                            cache: false,
							data: {
								{$this->buildJsRequestCommonParams($widget, $action)}
								data: requestData
								{$prefill}
							},
							success: function(data, textStatus, jqXHR) {
								var dialogId;
                                {$this->buildJsCloseDialog($widget, $input_element)}
		                       	if ($('#ajax-dialogs').length < 1){
		                       		$('body').append('<div id=\"ajax-dialogs\"></div>');
                       			}
								$('#ajax-dialogs').append('<div class=\"ajax-wrapper\">'+data+'</div>');
								dialogId = $('#ajax-dialogs').children().last().children('.easyui-dialog').attr('id');
                                
                                if (! dialogId) {
                                    {$this->buildJsShowError("'<div style=\"padding: 10px\">' + data + '</div>'", '"Unknown server error: dialog could not beloaded"')}
                                    $('#ajax-dialogs').children().last().remove();
                                    return;
                                }

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
									{$this->buildJsTriggerActionEffects($action)}
								};
                       			{$this->buildJsBusyIconHide()}
							},
							error: function(jqXHR, textStatus, errorThrown){
								{$this->buildJsShowErrorAjax('jqXHR')}
								{$this->buildJsBusyIconHide()}
							}
						});
						{$this->buildJsCloseDialog($widget, $input_element)} 
JS;
        }
        return $output;
    }

    /**
     * 
     * {@inheritdoc}
     * @see JqueryButtonTrait::buildJsCloseDialog()
     */
    protected function buildJsCloseDialog($widget, $input_element)
    {
        if ($widget instanceof DialogButton && $widget->getCloseDialogAfterActionSucceeds()){
            if ($widget->getInputWidget() instanceof Dialog){
                return "$('#" . $input_element->getId() . "').dialog('close');";
            } else {
                $dialog = $widget->getParentByClass(Dialog::class);
                if ($dialog) {
                    return "$('#" . $this->getFacade()->getElement($dialog)->getId() . "').dialog('close');";
                }
            }
        }
        return "";
    }    
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildHtmlHeadTags()
     */
    public function buildHtmlHeadTags()
    {
        // IMPORTANT: do not include head tags from children! Children of a button are widgets inside
        // it's action. Including them here would require rendering them, which severely impacts
        // performanc for complex UIs like the metamodel object editor.
        // Since this facade renders action-widgets by asking the server when the button is pressed
        // (see buildJsClickShowWidget() and buildJsClickShowDialog()) it is enough, to get the head
        // tags for the custom-script actions only.
        $includes = $this->buildHtmlHeadTagsForCustomScriptIncludes();
        if ((null !== $action = $this->getAction()) && $action instanceof iShowDialog && $action->isDialogLazyLoading(true) === false) {
            $includes = array_merge($includes, $this->getFacade()->getElement($action->getDialogWidget())->buildHtmlHeadTags());
        }
        return $includes;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsDisabler()
     */
    public function buildJsDisabler()
    {
        // setTimeout() required to make sure, the jEasyUI element was initialized (especially in lazy loading dialogs)
        return "setTimeout(function(){ $('#{$this->getId()}').{$this->getElementType()}('disable') }, 0)";
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsEnabler()
     */
    public function buildJsEnabler()
    {
        // setTimeout() required to make sure, the jEasyUI element was initialized (especially in lazy loading dialogs)
        return  "setTimeout(function(){ $('#{$this->getId()}').{$this->getElementType()}('enable') }, 0)";
    }
    
    public function buildCssElementClass()
    {
        $class = 'exf-button exf-element';
        switch ($this->getWidget()->getAppearance()) {
            case Button::APPEARANCE_LINK: $class .= ' exf-btn-link'; break;
            case Button::APPEARANCE_STROKED: $class .= ' exf-btn-stroked'; break;
            case Button::APPEARANCE_FILLED: $class .= ' exf-btn-filled'; break;
        }
        return $class;
    }
}