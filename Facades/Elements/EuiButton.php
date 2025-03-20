<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\DataTypes\MessageTypeDataType;
use exface\Core\Interfaces\Widgets\ConfirmationWidgetInterface;
use exface\Core\Widgets\DialogButton;
use exface\Core\Interfaces\Actions\ActionInterface;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryButtonTrait;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryAlignmentTrait;
use exface\Core\Widgets\Dialog;
use exface\Core\Widgets\Button;
use exface\Core\Widgets\ButtonGroup;
use exface\Core\Interfaces\Actions\iShowDialog;
use exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement;
use exface\Core\Facades\AbstractAjaxFacade\Interfaces\AjaxFacadeElementInterface;
use exface\Core\Interfaces\Widgets\iHaveConfigurator;
use exface\Core\Widgets\DataConfigurator;

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
        
        // Register an onChange-Script on the element linked by a disable condition and similar things.
        $this->registerConditionalPropertiesLiveRefs();
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
            $output .= <<<JS

				function {$this->buildJsClickFunctionName()}() {
					return {$click};
				}
JS;
        }
        
        // Get the java script required for the action itself
        if ($action) {
            // Actions with facade scripts may contain some helper functions or global variables.
            // Print the here first.
            if ($action && $action->implementsInterface('iRunFacadeScript')) {
                $output .= $this->getAction()->buildScriptHelperFunctions($this->getFacade());
            }
        }
        
        // Initialize the conditional peroperties of the widget if set.
        $output .= $this->buildjsConditionalProperties(true);
        
        // Refresh/reset widget explicitly mentioned in the Buttons UXON after
        // the action is performed
        $output .= $this->buildJsRegisterOnActionPerformed();
        
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
        $prefix = '';
        $cssClass = '';
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
        if (! $widget->getWidth()->isUndefined()) {
            $style .= ' width:' . $this->buildCssWidth();
        }
        if (! $widget->getHeight()->isUndefined()) {
            $style .= ' height:' . $this->buildCssHeight();
        }
        
        if ($widget->getIconSet() === 'svg' && null !== $icon = $widget->getIcon()) {
            $prefix = '<span class="l-btn-icon">' . $icon . '</span>';
            $cssClass = 'exf-svg-icon';
        }

        $output = '
				<a id="' . $this->getId() . '" title="' . $this->buildHintText($widget->getHint()) . '" href="#" class="easyui-' . $this->getElementType() . ' ' . $cssClass . '" data-options="' . $this->buildJsDataOptions() . '" style="' . $style . '" onclick="' . $this->buildJsFunctionPrefix() . 'click();">
                    ' . $prefix . $this->getCaption() . '
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
        if (null !== ($icon = $widget->getIcon()) && $widget->getShowIcon($showIconByDefault)) {
            $data_options .= ", iconCls: '" . $this->buildCssIconClass($icon) . "'";
        }
        
        return $data_options;
    }

    protected function buildJsClickShowDialog(ActionInterface $action, string $jsRequestData) : string
    {
        $widget = $this->getWidget();
        
        /* @var $prefill_link \exface\Core\CommonLogic\WidgetLink */
        $prefill = '';
        if ($prefill_link = $action->getPrefillWithDataFromWidgetLink()) {
            if ($prefill_link->getTargetPageAlias() === null || $widget->getPage()->is($prefill_link->getTargetPage())) {
                $prefill = ", prefill: " . $this->getFacade()->getElement($prefill_link->getTargetWidget())->buildJsDataGetter($action);
            }
        }
        
        $headers = ! empty($this->getAjaxHeaders()) ? 'headers: ' . json_encode($this->getAjaxHeaders()) . ',' : '';
        
        // NOTE: trigger action effects AFTER removing the closed dialog - otherwise
        // this might cause refreshes on the dialog tables that are totally useless!
        if (($action instanceof iShowDialog) && $action->isDialogLazyLoading(true) === false) {
            // For a non-lazy dialog store its content in a JSON and let the JS code append it to
            // the HTML once the dialog is opened and remove it again once it is closed. This way
            // non-lazy dialogs behave pretty much the same as normal lazy ones, so minimum side
            // effects are to be expected when turning off lazy_loading.
            $dialogData = [
                'html' => "{$this->getFacade()->getElement($action->getDialogWidget())->buildHtml()}",
                'js' => "<script>\n" . $this->getFacade()->getElement($action->getDialogWidget())->buildJs() . "\n</script>",
                'head' => $this->getFacade()->getElement($action->getDialogWidget())->buildHtmlHeadTags()
            ];
            $dialogDataJs = json_encode($dialogData);
            $output = <<<JS
            
                        {$this->buildJsCloseDialog()}
                        
                        (function(){
                            var onCloseFunc;
                            var oDialogData = $dialogDataJs;
                            var jqDialogWrapper = $('<div style="display:none" id="{$this->getId()}_DialogWrapper"></div>');
                            
                            oDialogData.head.forEach(function(sTag){
                                $(jqDialogWrapper).append(sTag);
                            });
                            jqDialogWrapper
                            .append(oDialogData.html)
                            .append(oDialogData.js);

                            jqDialogWrapper.appendTo('body');
                            $.parser.parse(jqDialogWrapper);

                            $('#{$this->getFacade()->getElement($action->getDialogWidget())->getId()}').dialog('open');
                        
                            onCloseFunc = $('#{$this->getFacade()->getElement($action->getDialogWidget())->getId()}').panel('options').onClose;
    						$('#{$this->getFacade()->getElement($action->getDialogWidget())->getId()}').panel('options').onClose = function(){
    							onCloseFunc();
                                $(this).dialog('destroy').remove();
                                jqDialogWrapper.remove();
                                {$this->buildJsTriggerActionEffects($action)}
    						};
                        })();
								
JS;
        } else {
            $output = <<<JS
						{$this->buildJsBusyIconShow()}
						$.ajax({
							type: 'POST',
							url: '{$this->getAjaxUrl()}',
                            {$headers}
							dataType: 'html',
                            cache: false,
							data: {
								{$this->buildJsRequestCommonParams($widget, $action)}
								data: {$jsRequestData}
								{$prefill}
							},
							success: function(data, textStatus, jqXHR) {
								var dialogId;
                                {$this->buildJsCloseDialog()}
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
						{$this->buildJsCloseDialog()} 
JS;
        }
        return $output;
    }

    /**
     * 
     * {@inheritdoc}
     * @see JqueryButtonTrait::buildJsCloseDialog()
     */
    protected function buildJsCloseDialog() : string
    {
        $widget = $this->getWidget();
        if ($widget instanceof DialogButton && $widget->getCloseDialogAfterActionSucceeds()){
            if ($widget->getInputWidget() instanceof Dialog){
                return "$('#" . $this->getInputElement()->getId() . "').dialog('close');";
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
        return $this->buildHtmlHeadTagsForCustomScriptIncludes();
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsSetDisabled()
     */
    public function buildJsSetDisabled(bool $trueOrFalse) : string
    {
        // setTimeout() required to make sure, the jEasyUI element was initialized (especially in lazy loading dialogs)
        if ($trueOrFalse === true) {
            return "setTimeout(function(){ $('#{$this->getId()}').{$this->getElementType()}('disable') }, 0)";
        } else {
            return "setTimeout(function(){ $('#{$this->getId()}').{$this->getElementType()}('enable') }, 0)";
        }
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
    
    /**
     * Registers a jQuery custom event handler that refreshes/resets other widget if required by the buttons config.
     *
     * jEasyUI currently does not support refresh/reset of containers or individual
     * elements via event, so we register this handler for each button to make sure
     * widgets, that are explicitly required to reset/refresh in the Buttons UXON
     * are affected.
     * 
     * jEasyUI data configurators, on the other hand, already listen to action performed
     * events, so they are explicitly filtered away.
     *
     * @param string $scriptJs
     * @return string
     */
    protected function buildJsRegisterOnActionPerformed() : string
    {
        // Don't bother if there is no action - this case is take care of
        // by the JqueryButtonTrait::buildJsClickNoAction()
        if ($this->getWidget()->hasAction() === false) {
            return '';
        }
        
        $actionperformed = AbstractJqueryElement::EVENT_NAME_ACTIONPERFORMED;
        $filterDataEls = function(AjaxFacadeElementInterface $el) {
            $w = $el->getWidget();
            return ! (
                ($w instanceof iHaveConfigurator) 
                && ($w->getConfiguratorWidget() instanceof DataConfigurator)
            );
        };
        $js = $this->buildJsRefreshWidgets(false, $filterDataEls) . $this->buildJsResetWidgets(false, $filterDataEls);
        if (trim($js === '')) {
            return '';
        }
        return <<<JS
        
$( document ).off( "{$actionperformed}.{$this->getId()}" );
$( document ).on( "{$actionperformed}.{$this->getId()}", function( oEvent, oParams ) {
    if (oParams.trigger_widget_id === "{$this->getId()}") {
        $js
    }
});

JS;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsDestroy()
     */
    public function buildJsDestroy() : string
    {
        $actionperformed = AbstractJqueryElement::EVENT_NAME_ACTIONPERFORMED;
        return "$( document ).off( '{$actionperformed}.{$this->getId()}' );";
    }

    /**
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryButtonTrait::buildJsConfirmation()
     */
    protected function buildJsConfirmation(ConfirmationWidgetInterface $widget, string $jsRequestData, string $onContinueJs, string $onCancelJs = null)
    {
        switch ($widget->getType()) {
            case MessageTypeDataType::WARNING: $class = 'messager-warning'; break;
            case MessageTypeDataType::ERROR: $class = 'messager-error'; break;
            case MessageTypeDataType::QUESTION: $class = 'messager-question'; break;
            default: $class = 'messager-info'; break;
        }
        return <<<JS
            $.messager.confirm({
                title: {$this->escapeString($widget->getCaption())},
                msg: {$this->escapeString($widget->getQuestionText())},
                ok: {$this->escapeString($widget->getButtonContinue()->getCaption())},
                cancel: {$this->escapeString($widget->getButtonCancel()->getCaption())},
                fn: function(confirm){
                    if (confirm){
                        $onContinueJs;
                    } else {
                        $onCancelJs;
                    }
                }
            });
            $('.messager-window > .messager-button > a').each(function(i, domBtn){
                if (i > 0) {
                    $(domBtn).addClass('l-btn-plain');
                }
            });
            $('.messager-body > .messager-icon').removeClass('messager-question').addClass('$class');
JS;
    }
}