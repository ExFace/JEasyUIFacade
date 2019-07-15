<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JExcelTrait;
use exface\JEasyUIFacade\Facades\Elements\Traits\EuiPanelWrapperTrait;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryToolbarsTrait;

class EuiDataImporter extends EuiAbstractElement
{
    use JExcelTrait;
    use JqueryToolbarsTrait;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::buildHtml()
     */
    public function buildHtml()
    {
        $toolbar = $this->buildHtmlToolbars();
        
        return <<<HTML

        <div class="datatable-toolbar datagrid-toolbar" style="height: 36px">
            {$toolbar}
        </div>
        {$this->buildHtmlJExcel()}

HTML;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::buildJs()
     */
    public function buildJs()
    {
        // If there is a preview-button, we need to call the data setter with it's
        // result after it's action was called successfully. 
        if ($this->getWidget()->hasPreview() === true) {
            $this->getFacade()->getElement($this->getWidget()->getPreviewButton())->addOnSuccessScript($this->buildJsDataSetter('response'));
        }
        return <<<JS
        setTimeout(function(){
            {$this->buildJsJExcelInit()}
        }, 0);
        {$this->buildJsToolbars()}
        {$this->buildJsFunctionsForJExcel()}

JS;
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