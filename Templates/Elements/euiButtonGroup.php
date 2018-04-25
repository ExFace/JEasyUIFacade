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
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildHtml()
     */
    public function buildHtml()
    {
        return $this->buildHtmlButtonGroupWrapper($this->buildHtmlButtons());
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJs()
     */
    public function buildJs()
    {
        $js = '';
        foreach ($this->getWidget()->getButtons() as $button) {
            $js .= $this->getTemplate()->buildJs($button);
        }
        return $js;
    }
}
?>