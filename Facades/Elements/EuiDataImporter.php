<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JExcelTrait;
use exface\JEasyUIFacade\Facades\Elements\Traits\EuiPanelWrapperTrait;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryToolbarsTrait;

class EuiDataImporter extends EuiAbstractElement
{
    use JExcelTrait;#
    use EuiPanelWrapperTrait;
    use JqueryToolbarsTrait;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::buildHtml()
     */
    public function buildHtml()
    {
        return $this->buildHtmlJExcel();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::buildJs()
     */
    public function buildJs()
    {
        return $this->buildJsJExcelInit();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\HtmlBrowserTrait::buildCssElementStyle()
     */
    public function buildCssElementStyle()
    {
        return 'width: 100%; height: 100%;';
    }
    
    public function buildHtmlHeadTags()
    {
        return $this->buildHtmlHeadTagsForJExcel();
    }
}