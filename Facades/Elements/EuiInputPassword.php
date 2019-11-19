<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\InputPassword;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryInputTrait;

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
    
    public function buildJs()
    {
        return parent::buildJs() . <<<JS

				setTimeout(function(){ $('#{$this->getId()}').parent().find('input').prop('type', 'password'); }, 0);
JS;
    }
}