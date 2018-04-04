<?php
namespace exface\JEasyUiTemplate\Templates\Elements;

use exface\Core\Interfaces\Actions\ActionInterface;
use exface\Core\Widgets\Input;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryLiveReferenceTrait;
use exface\Core\Factories\WidgetLinkFactory;

/**
 *
 * @method Input getWidget()
 *        
 * @author Andrej Kabachnik
 *        
 */
class euiInput extends euiValue
{
    use JqueryLiveReferenceTrait;

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiText::init()
     */
    protected function init()
    {
        parent::init();
        $this->setElementType('textbox');
        // If the input's value is bound to another element via an expression, we need to make sure, that other element will
        // change the input's value every time it changes itself. This needs to be done on init() to make sure, the other element
        // has not generated it's JS code yet!
        $this->registerLiveReferenceAtLinkedElement();
        
        // Register an onChange-Script on the element linked by a disable condition.
        $this->registerDisableConditionAtLinkedElement();
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiText::buildHtml()
     */
    public function buildHtml()
    {
        /* @var $widget \exface\Core\Widgets\Input */
        $widget = $this->getWidget();
        
        $output = '	<input style="height: 100%; width: 100%;"
						name="' . $widget->getAttributeAlias() . '" 
						value="' . $this->getValueWithDefaults() . '" 
						id="' . $this->getId() . '"  
						' . ($widget->isRequired() ? 'required="true" ' : '') . '
						' . ($widget->isDisabled() ? 'disabled="disabled" ' : '') . '
						/>
					';
        return $this->buildHtmlLabelWrapper($output);
    }

    /**
     * Returns the escaped and ready-to-use value of the widget including the default value (if applicable).
     *
     * @return string
     */
    public function getValueWithDefaults()
    {
        return $this->escapeString($this->getWidget()->getValueWithDefaults());
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiText::buildJs()
     */
    public function buildJs()
    {
        $output = '';
        $output .= "
				$('#" . $this->getId() . "')." . $this->getElementType() . "(" . ($this->buildJsDataOptions() ? '{' . $this->buildJsDataOptions() . '}' : '') . ");//.textbox('addClearBtn', 'icon-clear');
				";
        $output .= $this->buildJsEventScripts();
        return $output;
    }
    
    /**
     * Returns JS scripts for event handling like live references, onChange-handlers,
     * disable conditions, etc.
     * 
     * @return string
     */
    protected function buildJsEventScripts()
    {
        $output = '';
        $output .= $this->buildJsLiveReference();
        $output .= $this->buildJsOnChangeHandler();
        
        // Initialize the disabled state of the widget if a disabled condition is set.
        $output .= $this->buildJsDisableConditionInitializer();
        return $output;        
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiAbstractElement::buildJsInitOptions()
     */
    public function buildJsInitOptions()
    {
        return $this->buildJsDataOptions();
    }

    /**
     * 
     * @return string
     */
    protected function buildJsDataOptions()
    {
        $options = '';
        
        if ($this->getOnChangeScript()) {
            $options .= "\n" . 'onChange: function(newValue, oldValue) {$(this).trigger("change");}';
        }
        
        return $options;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJsValueSetterMethod()
     */
    public function buildJsValueSetterMethod($value)
    {
        return $this->getElementType() . '("setValue", ' . $value . ').trigger("change")';
    }

    /**
     * 
     * @return string
     */
    protected function buildJsOnChangeHandler()
    {
        if ($this->getOnChangeScript()) {
            return "$('#" . $this->getId() . "').change(function(event){" . $this->getOnChangeScript() . "});";
        } else {
            return '';
        }
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJsDataGetter($action, $custom_body_js)
     */
    public function buildJsDataGetter(ActionInterface $action = null)
    {
        if ($this->getWidget()->isDisplayOnly()) {
            return '{}';
        } else {
            return parent::buildJsDataGetter($action);
        }
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJsValidator()
     */
    function buildJsValidator()
    {
        $widget = $this->getWidget();
        
        $must_be_validated = ! ($widget->isHidden() || $widget->isReadonly() || $widget->isDisabled() || $widget->isDisplayOnly());
        if ($must_be_validated) {
            $output = "$('#{$this->getId()}').{$this->getElementType()}('isValid')";
        } elseif ($widget->isRequired()) {
            $output = '(' . $this->buildJsValueGetter() . ' === "" ? false : true)';
        } else {
            $output = 'true';
        }
        
        return $output;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryLiveReferenceTrait::buildJsDisableCondition()
     */
    public function buildJsDisableCondition()
    {
        $output = '';
        $widget = $this->getWidget();
        
        if (($condition = $widget->getDisableCondition()) && $condition->hasProperty('widget_link')) {
            $link = WidgetLinkFactory::createFromWidget($widget, $condition->getProperty('widget_link'));
            $linked_element = $this->getTemplate()->getElement($link->getTargetWidget());
            if ($linked_element) {
                switch ($condition->getProperty('comparator')) {
                    case EXF_COMPARATOR_IS_NOT: // !=
                    case EXF_COMPARATOR_EQUALS: // ==
                    case EXF_COMPARATOR_EQUALS_NOT: // !==
                    case EXF_COMPARATOR_LESS_THAN: // <
                    case EXF_COMPARATOR_LESS_THAN_OR_EQUALS: // <=
                    case EXF_COMPARATOR_GREATER_THAN: // >
                    case EXF_COMPARATOR_GREATER_THAN_OR_EQUALS: // >=
                        $enable_widget_script = $widget->isDisabled() ? '' : $this->buildJsEnabler() . ';
							// Sonst wird ein leeres required Widget nicht als invalide angezeigt
							$("#' . $this->getId() . '").' . $this->getElementType() . '("validate");';
                        
                        $output = <<<JS

						if ({$linked_element->buildJsValueGetter($link->getTargetColumnId())} {$condition->getProperty('comparator')} "{$condition->getProperty('value')}") {
							{$this->buildJsDisabler()};
						} else {
							{$enable_widget_script}
						}
JS;
                        break;
                    case EXF_COMPARATOR_IN: // [
                    case EXF_COMPARATOR_NOT_IN: // ![
                    case EXF_COMPARATOR_IS: // =
                    default:
                    // TODO fuer diese Comparatoren muss noch der JavaScript generiert werden
                }
            }
        }
        return $output;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJsEnabler()
     */
    function buildJsEnabler()
    {
        return '$("#' . $this->getId() . '").' . $this->getElementType() . '("enable")';
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJsDisabler()
     */
    function buildJsDisabler()
    {
        return '$("#' . $this->getId() . '").' . $this->getElementType() . '("disable")';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiText::buildCssHeightDefaultValue()
     */
    protected function buildCssHeightDefaultValue()
    {
        return ($this->getHeightRelativeUnit() * 1) . 'px';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiValue::buildCssElementClass()
     */
    public function buildCssElementClass()
    {
        return parent::buildCssElementClass() . ' exf-input';
    }
}
?>