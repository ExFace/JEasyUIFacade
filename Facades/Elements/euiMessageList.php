<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryMessageListTrait;

class EuiMessageList extends EuiContainer
{
    use JqueryMessageListTrait;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiContainer::buildHtml()
     */
    public function buildHtml()
    {
        return $this->buildHtmlGridItemWrapper($this->buildHtmlMessageList());
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::getWidth()
     */
    public function getWidth()
    {
        if ($this->getWidget()->getWidth()->isUndefined()) {
            $this->getWidget()->setWidth('max');
        } 
        return parent::getWidth();
    }
    
    public function getPadding($default = 0)
    {
        return 0;
    }
    
    protected function buildCssHeightDefaultValue()
    {
        return '';
    }
}