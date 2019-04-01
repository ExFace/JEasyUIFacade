<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryButtonGroupTrait;

/**
 * The jEasyUI implementation of the ButtonGroup widget
 * 
 * @author Andrej Kabachnik
 *        
 * @method ButtonGroup getWidget()
 */
class EuiButtonGroup extends EuiAbstractElement
{
    use JqueryButtonGroupTrait;
    
    protected function getMoreButtonsMenuCaption(){
        return '...';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildHtml()
     */
    public function buildHtml()
    {
        return $this->buildHtmlButtonGroupWrapper($this->buildHtmlButtons());
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJs()
     */
    public function buildJs()
    {
        $this->buildJsForButtons();
    }
}
?>