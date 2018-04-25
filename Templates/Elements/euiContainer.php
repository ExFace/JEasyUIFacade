<?php
namespace exface\JEasyUiTemplate\Templates\Elements;

use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryContainerTrait;

class euiContainer extends euiAbstractElement
{
    use JqueryContainerTrait;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildHtml()
     */
    public function buildHtml()
    {
        return $this->buildHtmlForChildren();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJs()
     */
    public function buildJs()
    {
        return $this->buildJsForChildren();
    }
}
?>