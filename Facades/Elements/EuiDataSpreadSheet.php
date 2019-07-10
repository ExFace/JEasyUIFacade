<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\JEasyUIFacade\Facades\Elements\Traits\EuiDataElementTrait;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JExcelTrait;

class EuiDataSpreadSheet extends EuiData
{    
    use EuiDataElementTrait;
    use JExcelTrait;

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiDataTable::buildHtml()
     */
    public function buildHtml()
    {
        return $this->buildHtmlPanelWrapper($this->buildHtmlJExcel());
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiDataTable::buildJs()
     */
    public function buildJs()
    {        
        return <<<JS
        
    {$this->buildJsForPanel()}
    {$this->buildJsJExcelInit()}
    {$this->buildJsDataLoadFunction()}
    {$this->buildJsRefresh()}

JS;
    }
    
    /**
     * 
     * @see EuiDataElementTrait::buildJsDataLoaderOnLoaded()
     */
    protected function buildJsDataLoaderOnLoaded(string $dataJs): string
    {
        return $this->buildJsDataSetter($dataJs);
    }
    
    public function buildHtmlHeadTags()
    {
        return array_merge(
            parent::buildHtmlHeadTags(),
            $this->buildHtmlHeadTagsForJExcel()
        );
    }
    
    public function buildCssElementClass()
    {
        return parent::buildCssElementClass() . ' exf-spreadsheet';
    }
}