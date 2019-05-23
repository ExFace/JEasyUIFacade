<?php
namespace exface\JEasyUIFacade\Facades\Elements;
use exface\Core\Facades\AbstractAjaxFacade\Elements\HtmlBrowserTrait;
class EuiBrowser extends EuiAbstractElement
{
    use HtmlBrowserTrait;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::buildHtml()
     */
    public function buildHtml()
    {
        return $this->buildHtmlIFrame();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::buildJs()
     */
    public function buildJs()
    {
        return '';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\HtmlBrowserTrait::buildCssElementStyle()
     */
    public function buildCssElementStyle()
    {
        return 'width: 100%; height: calc(100% - 3px); border: 0;';
    }
}