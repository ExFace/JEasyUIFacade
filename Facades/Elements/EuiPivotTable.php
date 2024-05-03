<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Interfaces\DataSheets\DataSheetInterface;
use exface\Core\Interfaces\WidgetInterface;
use exface\JEasyUIFacade\Facades\JEasyUIFacade;
use exface\JEasyUIFacade\Facades\Elements\Traits\EuiDataElementTrait;
use exface\Core\Facades\AbstractAjaxFacade\Elements\PivotTableTrait;

/**
 * Rendes PivotTable widgets via PivotTable.js library
 * 
 * ## Conflicts with jEasyUI
 * 
 * **IMPORTANT**: PivotTable.js uses jQuery UI and that produces a lot of conflicts with jEasyUI because
 * many of the jQuery plugins have the same names. To avoid this, we use a trick here: 
 * 1. right before PivotTable.js is loaded, we remove the then-available `jQuery` object and 
 * load jQuery again to make it register a fresh copy of itself.
 * 2. When PivotTable.js and all of its dependencies like jQuery UI are loaded, they find a clean 
 * version of jQuery and use it.
 * 3. once all PivotTable.js JS scripts are loaded, we restore the original `jQuery` and `$` objects
 * and keep the special pivot version as `jQueryPivot`. 
 * 
 * **IMPLICATIONS**: all JS scripts, that address features of PivotTable.js MUST be called with the special
 * jQuery version: e.g. `jQueryPivot('.selector').pivot()`.
 * 
 * @author andrej.kabachnik
 *
 */
class EuiPivotTable extends EuiData
{   
    use EuiDataElementTrait;
    
    use PivotTableTrait;
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiDataTable::buildHtml()
     */
    public function buildHtml()
    {
        return $this->buildHtmlPanelWrapper($this->buildHtmlPivot());
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiDataTable::buildJs()
     */
    public function buildJs()
    {
        return <<<JS

    setTimeout(function() {
        {$this->buildJsRefresh()}
        setTimeout(function(){
            {$this->buildJsResize()}
        }, 100);
    }, 0);
    {$this->buildJsForPanel()}   
    {$this->buildJsDataLoadFunction()}
    
JS;
    }
    
    /**
     *
     * @see EuiDataElementTrait::buildJsDataLoaderOnLoaded()
     */
    protected function buildJsDataLoaderOnLoaded(string $dataJs): string
    {
        return <<<JS
    (function(jQuery){
        var $ = jQuery;        
        {$this->buildJsPivotRender($dataJs.'.pivotdata')}
    })(jQueryPivot);
JS;
    }
    
    /**
     * Returns JavaScript and CSS headers, needed for the element as an array of lines.
     * 
     * See trait docs above for details!
     * 
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildHtmlHeadTags()
     */
    public function buildHtmlHeadTags()
    {
        $includes = parent::buildHtmlHeadTags();
        $facade = $this->getFacade();
        // Replace the current version of jQuery with a clean one, so that jQuery UI does not interfere
        // with jEasyUI!
        $includes[] = '<script type="text/javascript">
                            // Backup the current state of jQuery and load a fresh copy for jQueryUI and Pivottable to work
                            var jQueryMain = $.noConflict(true);
                        </script>';
        // Load a clean version of jQuery here
        $includes[] = '<script type="text/javascript" src="' . $facade->buildUrlToSource('LIBS.JQUERY') . '"></script>';
        // Load everything required for PivotTable to work
        $includes = array_merge($includes, $this->buildHtmlHeadTagsForPivot());
        // Restore the original version of jQuery and save the special pivot-version in jQueryPivot.
        $includes[] = '<script type="text/javascript">
                            // Restore the normal version of jQuery
                            var jQueryPivot = $.noConflict(true);
                            jQuery = jQueryMain;
                            $ = jQueryMain;
                        </script>';
        
        return $includes;
    }
    
    /**
     *
     * @return string
     */
    protected function buildJsResize() : string
    {
        return $this->buildJsResizeInnerWidget();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiDataTable::getElementType()
     */
    public function getElementType() : ?string
    {
        return null;
    }

    /**
     * A pivotGrid expects data in a different format: [ {field: value, ...}, {...}, ...]
     * 
     * @return array
     */
    public static function buildResponseData(JEasyUIFacade $facade, DataSheetInterface $data_sheet, WidgetInterface $widget)
    {
        $data = array();
        foreach ($data_sheet->getRows() as $row_nr => $row) {
            foreach ($row as $fld => $val) {
                if ($col = $widget->getColumnByDataColumnName($fld)) {
                    $data[$row_nr][$col->getCaption()] = $val;
                }
            }
        }
        return [
            'pivotdata' => $data
        ];
    }
    
    /**
     * Returns the jQuery element for jExcel - e.g. $('#element_id') in most cases.
     * @return string
     */
    protected function buildJsJqueryElement() : string
    {
        return "jQueryPivot('#{$this->getId()}')";
    }
}