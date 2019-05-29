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
        if ($this->getWidget()->hasPreview() === true) {
            $dataRefresh = <<<JS

var aData = [];
if (response.rows) {
    response.rows.forEach(function(oRow) {
        aData.push(Object.values(oRow));
    });
}
if (aData.length > 0) {
    $('#{$this->getId()}').jexcel('setData', aData);
}

JS;
            $this->getFacade()->getElement($this->getWidget()->getPreviewButton())->addOnSuccessScript($dataRefresh);
        }
        return <<<JS

        {$this->buildJsJExcelInit()}
        {$this->buildJsToolbars()}

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