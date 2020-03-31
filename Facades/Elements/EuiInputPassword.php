<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\InputPassword;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryInputTrait;
use exface\Core\Factories\WidgetFactory;
use exface\Core\Interfaces\WidgetInterface;

/**
 * Renders a jEasyUI textbox and changes the input type to password.
 *
 * @method InputPassword getWidget()
 *
 * @author Andrej Kabachnik
 *
 */
class EuiInputPassword extends EuiInput
{
    use JqueryInputTrait;
            
    private $conformationInputWidget = null;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::buildHtmlGridItemWrapper()
     */
    public function buildHtmlGridItemWrapper($html, $title = '')
    {        
        $widget = $this->getWidget();
        if ($widget->getShowSecondInputForConfirmation() === false) {
            return parent::buildHtmlGridItemWrapper($html, $title); 
        }
        
        $secondInputHtml =  '	<input style="height: 100%; width: 100%;"
						id="' . $this->getConfirmationInput()->getId() . '"
						' . ($widget->isRequired() ? 'required="true" ' : '') . '
						' . ($widget->isDisabled() ? 'disabled="disabled" ' : '') . '
						/>
					';
        return parent::buildHtmlGridItemWrapper('<div>' . $html . '</div>' . '<div>' . $this->getFacade()->getElement($this->getConfirmationInput())->buildHtmlLabelWrapper($secondInputHtml, false) . '</div>', $title);
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiValue::buildCssElementClass()
     */
    public function buildCssElementClass()
    {
        return parent::buildCssElementClass() . ' exf-inputPassword';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::getHeight()
     */
    public function getHeight()
    {
        $widget = $this->getWidget();
        if ($widget->getShowSecondInputForConfirmation() === false) {
            return parent::getHeight();
        }
        
        // double height for confirm input, if in px
        $output = parent::getHeight();
        if (substr($output, -2) === 'px') {
            $height = intval(substr($output, 0, -2)) * 2;
            $output = $height . 'px';
        }
        return $output;
    }
        
    /**
     * returns the password confirmation input widget
     * 
     * @return WidgetInterface
     */
    protected function getConfirmationInput() : WidgetInterface
    {
        if ($this->conformationInputWidget === null) {
            $widget = $this->getWidget();
            $confirmWidget = WidgetFactory::create($widget->getPage(), $widget->getWidgetType());
            $confirmWidget->setMetaObject($this->getMetaObject());
            $confirmWidget->setCaption($this->translate("WIDGET.INPUTPASSWORD.CONFIRM"));
            $confirmWidget->setWidth('100%');
            $this->conformationInputWidget = $confirmWidget;
        }
        return $this->conformationInputWidget;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJs()
     */
    public function buildJs()
    {
        $initSecondInput = '';
        if ($this->getWidget()->getShowSecondInputForConfirmation() === true) {
            $confirmInputElement = $this->getFacade()->getElement($this->getConfirmationInput());
            $initSecondInput = $confirmInputElement->buildJs();
            $onChangeScript = <<<JS
            
                        if ({$this->buildJsValueGetter()} === '') {
                            {$confirmInputElement->buildJsDisabler()}
                        } else {
                            {$confirmInputElement->buildJsEnabler()}
                        }
JS;
            $this->addOnChangeScript($onChangeScript);
        }
        
        return parent::buildJs() . <<<JS
        
				setTimeout(function(){
                    var elements = $('#{$this->getId()}').parent().find('input');
                    elements.each(function() {
                        if ($(this).prop('type') !== 'hidden') {
                            $(this).prop('type', 'password');
                        }
                    })                    
                }, 0);
                {$initSecondInput}
JS;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJsValidator()
     */
    public function buildJsValidator()
    {
        if ($this->getWidget()->getShowSecondInputForConfirmation() === true) {
            $confirmInputElement = $this->getFacade()->getElement($this->getConfirmationInput());
            return "({$this->buildJsValueGetter()} === {$confirmInputElement->buildJsValueGetter()})";
        }
        return 'true';
    }
    
}