<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryToolbarTrait;

/**
 * The jEasyUI implementation of the Toolbar widget
 *
 * @author Andrej Kabachnik
 *        
 * @method Toolbar getWidget()
 */
class EuiToolbar extends EuiAbstractElement
{
    use JqueryToolbarTrait;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJs()
     */
    public function buildJs()
    {
        return $this->buildJsButtons();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildHtml()
     */
    public function buildHtml()
    {
        return $this->buildHtmlToolbarWrapper($this->buildHtmlButtons());
    }
}
?>