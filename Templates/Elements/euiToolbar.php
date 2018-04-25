<?php
namespace exface\JEasyUiTemplate\Templates\Elements;

use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryToolbarTrait;

/**
 * The jEasyUI implementation of the Toolbar widget
 *
 * @author Andrej Kabachnik
 *        
 * @method Toolbar getWidget()
 */
class euiToolbar extends euiAbstractElement
{
    use JqueryToolbarTrait;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJs()
     */
    public function buildJs()
    {
        return $this->buildJsButtons();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildHtml()
     */
    public function buildHtml()
    {
        return $this->buildHtmlToolbarWrapper($this->buildHtmlButtons());
    }
}
?>