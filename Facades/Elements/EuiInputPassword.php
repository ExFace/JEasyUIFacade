<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\InputPassword;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryInputTrait;
use exface\Core\Factories\WidgetFactory;

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
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiText::init()
     */
    protected function init()
    {
        parent::init();
        /*if ($this->getWidget()->getShowSecondInputForConfirmation() === true) {
            $confirmWidget = WidgetFactory::create($this->getWidget()->getPage(), 'InputPassword', $this->getWidget()->getParent());
            $confirmWidget->setCaption($this->translate("WIDGET.CONFIRM_PASSWORD"));
            $confirmWidget->setId($confirmWidget->getId() . 'Confirm');
            $confirmElement = new EuiInputPassword($confirmWidget, $this->getFacade());
        }*/
    }
        
    public function buildJs()
    {
        return parent::buildJs() . <<<JS
        
				setTimeout(function(){ $('#{$this->getId()}').parent().find('input').prop('type', 'password'); }, 0);
JS;
    }
    
}