<?php
namespace exface\JEasyUiTemplate\Templates\Elements;

use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryButtonGroupTrait;

/**
 * The jEasyUI implementation of the ButtonGroup widget
 * 
 * @author Andrej Kabachnik
 *        
 * @method ButtonGroup getWidget()
 */
class euiButtonGroup extends euiAbstractElement
{
    use JqueryButtonGroupTrait;
    
    protected function getMoreButtonsMenuCaption(){
        return '...';
    }
}
?>