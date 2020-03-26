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
    
    public function buildJs()
    {
        $initSecondInput = '';
        if ($this->getWidget()->getShowSecondInputForConfirmation() === true) {
            $initSecondInput = $this->getFacade()->getElement($this->getConfirmationInput())->buildJs();
        }
        
        return parent::buildJs() . <<<JS
        
				setTimeout(function(){ $('#{$this->getId()}').parent().find('input').prop('type', 'password'); }, 0);
                {$initSecondInput}
JS;
    }
    
    public function buildJsValidator()
    {
        if ($this->getWidget()->getShowSecondInputForConfirmation() === true) {
            $confirmInputElement = $this->getFacade()->getElement($this->getConfirmationInput());
            return "{$this->buildJsValueGetter()} === {$confirmInputElement->buildJsValueGetter()}";
        }
        return 'true';
    }
    
}