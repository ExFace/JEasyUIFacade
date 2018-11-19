<?php
namespace exface\JEasyUiTemplate\Templates\Elements;

use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryMessageListTrait;

class euiMessageList extends euiContainer
{
    use JqueryMessageListTrait;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiContainer::buildHtml()
     */
    public function buildHtml()
    {
        return $this->buildHtmlGridItemWrapper($this->buildHtmlMessageList());
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiAbstractElement::getWidth()
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