<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\JEasyUIFacade\Facades\Elements\Traits\EuiDataElementTrait;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JExcelTrait;

class EuiDataSpreadSheet extends EuiData
{    
    use EuiDataElementTrait {
        init as initViaTrait;
    }
    use JExcelTrait;
    
    protected function init()
    {
        $this->initViaTrait();
        $this->registerReferencesAtLinkedElements();
        $this->addOnLoadSuccess($this->buildJsFooterRefresh('data', 'jqSelf'));
    }

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
    setTimeout(function() {
        {$this->buildJsJExcelInit()}
        {$this->buildJsRefresh()}
    }, 0);
    
    {$this->buildJsDataLoadFunction()}

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