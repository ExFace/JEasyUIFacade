<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\DataTypes\AggregatorFunctionsDataType;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryDataTransposerTrait;


/**
 *
 * @method exface\Core\Widgets\DataMatrix getWidget()
 *        
 * @author Andrej Kabachnik
 *        
 */
class EuiDataMatrix extends EuiDataTable
{
    use JqueryDataTransposerTrait;

    protected function init()
    {
        parent::init();
        $this->addOnLoadSuccess($this->buildJsCellMerger());
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiDataTable::getElementType()
     */
    public function getElementType() : ?string
    {
        return 'datagrid';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiData::getLoadFilterScript()
     */
    protected function getLoadFilterScript(string $dataJs) : ?string
    {
        // Transpose every time data is loaded (using the loadFilter of the datagrid)
        // WORKAROUND for the pager being reinitialized every time for some reason -
        // just adding the pager buttons back here again
        return parent::getLoadFilterScript($dataJs) . $this->buildJsTransposeColumns($dataJs, $this->buildJsInitPager());
    }

    /**
     * @return string
     */
    protected function buildJsTransposeColumns(string $dataJs, string $onTransposedJs = '') : string
    {
        $colModelsJs = $this->buildJsTransposerColumnModels();
        $widget = $this->getWidget();
        foreach ($widget->getColumns() as $col) {
            $cellElem = $this->getFacade()->getElement($col->getCellWidget());
            $colKey = $col->getDataColumnName() ? $col->getDataColumnName() : $col->getId();
            $colModelsFacadePropsJs .= "
    oColModels['$colKey'].fnEuiStyler = {$this->buildJsInitOptionsColumnStyler($col, 'value', 'oRow', 'iRowIdx', 'null')}; 
    oColModels['$colKey'].fnFormatter = function(value){return {$cellElem->buildJsValueDecorator('value')} }; ";
    
        }
        
        $transpose_js = <<<JS

var jqSelf = $(this);
var aColsOrig = jqSelf.data('_columnsBkp'); // [{field: '...', title: '...'}, ...];
var oColModels = jqSelf.data('_exfColumnModels');
var iFrozenColCnt = {$widget->getFreezeColumns()};

$("#{$this->getId()}").data("_skipNextLoad", true);

if (! oColModels) {
    aColsOrig = jqSelf.datagrid('options').columns;
    if ({$widget->getFreezeColumns()} > 0) {
        for (var fi = jqSelf.datagrid('options').frozenColumns[0].length-1; fi >= 0; fi--) {
            aColsOrig[0].unshift(jqSelf.datagrid('options').frozenColumns[0][fi]);
        }
    }
    // FIXME remove in favor of oColModels
    jqSelf.data('_columnsBkp', aColsOrig);

    oColModels = {$colModelsJs};

    // Add facade-specific column models parts
    $colModelsFacadePropsJs
    aColsOrig.forEach(function(aColRow, iColRowIdx){
        aColRow.forEach(function(oColOpts, iColIdx) {
            oColModels[oColOpts.field].oEuiColOptions = oColOpts;
        });
    });

    // Cache the column models
    jqSelf.data('_exfColumnModels', oColModels);
}

var oTransposed = {$this->buildJsTranspose($dataJs, 'oColModels')}

// Replace columns in jEasyUI

var aColsNew = [];
var aColsNewFrozen = [];
aColsOrig.forEach(function(aEuiColRow, iEuiColRowIdx){
    aColsNew.push([]);
    aEuiColRow.forEach(function(oEuiCol) {
        var oCol = oTransposed.oColModelsTransposed[oEuiCol.field] || oTransposed.oColModelsOriginal[oEuiCol.field];
        switch (true) {
            case oCol.aReplacedWithColumnKeys.length > 0:
                oCol.aReplacedWithColumnKeys.forEach(function(sColKey){
                    var oReplCol = oTransposed.oColModelsTransposed[sColKey];
                    var oReplOpts = oReplCol.oEuiColOptions || {};

                    oReplOpts.field = oReplCol.sDataColumnName;
                    oReplOpts.title = oReplCol.sCaption ? '<span title="'+oReplCol.sHint+'">'+oReplCol.sCaption+'</title>' : '';
                    oReplOpts.hidden = oReplCol.bHidden;
                    oReplOpts.sortable = oReplCol.bSortable;
                    oReplOpts.formatter = oReplCol.fnFormatter;
                    if (oReplCol.sAlign !== null) {
                        oReplOpts.align = oReplCol.sAlign;
                    }
                    
                    aColsNew[iEuiColRowIdx].push(oReplOpts);
                });
                break;
            case oCol.bTransposeData === true:
                // Eventually we need to apply stylers of the transposed columns here (= style cells of the
                // the rows, that resulted from the transposed values)
                if (oCol.fnEuiStyler && oTransposed.oDataTransposed.rows) {
                    oTransposed.oDataTransposed.rows = function(aRows, oCol){
                        aRows.forEach(function(oRow, r) {
                            var oCellCol;
                            if (r % (oCol.iTransposedToSubrow+1) !== 0) {
                                for (var fld in oRow) {
                                    oCellCol = oTransposed.oColModelsTransposed[fld];
                                    if (oCellCol && oCellCol.bTransposedColumn === true && oCellCol.bHidden === false) {
                                        aRows[r][fld] = '<span style="' + oCol.fnEuiStyler(oRow[fld]) + '">' + oRow[fld] + '</span>';
                                    }
                                }
                            }
                            
                        });
                        return aRows;
                    }(oTransposed.oDataTransposed.rows, oCol);
                }

                /* FIXME initialize editors for transposed columns, but where to save the inputs???
                    for (var i in oColsTransposed){
                    	if (oColsTransposed[i].column.editor != undefined){
                    		for (var j=0; j<newColRow.length; j++){
                    			if (newColRow[j]._transposedFields != undefined && newColRow[j]._transposedFields.indexOf(i) > -1){
                    				newColRow[j].editor = oColsTransposed[i].column.editor;
                    			}
                    		}
                    	}
                    }
                    jqSelf.datagrid({frozenColumns: aColsNewFrozen, columns: aColsNew});
                */
                break;
            default:
                aColsNew[iEuiColRowIdx].push(oEuiCol);
                break;
        }
    });
});

if (iFrozenColCnt > 0) {
    aColsNewFrozen.push([]);
    for (var i = 0; i < newColRow.length; i++) {
        if (newColRow[i].hidden !== true && i < iFrozenColCnt) {
            aColsNewFrozen[0].push(newColRow[i]);
            newColRow.splice(i, 1);
        }
    }
}

jqSelf.datagrid({frozenColumns: aColsNewFrozen, columns: aColsNew});
        
{$onTransposedJs}

return oTransposed.oDataTransposed;

JS;
        return $transpose_js;
    }

    protected function buildJsCellMerger()
    {
        $fields_to_merge = array();
        foreach ($this->getWidget()->getColumnsRegular() as $col) {
            $fields_to_merge[] = $col->getDataColumnName();
        }
        $fields_to_merge = json_encode($fields_to_merge);
        $rowspan = count($this->getWidget()->getColumnsTransposed());
        
        $output = <<<JS

        (function(){
			var fields = {$fields_to_merge};
            var iRowCnt = $(this).datagrid('getRows').length;
            if (! iRowCnt) return;
			for (var i=0; i<fields.length; i++){
	            for(var j=0; j< iRowCnt; j++){
	                $(this).datagrid('mergeCells',{
	                    index: j,
	                    field: fields[i],
	                    rowspan: {$rowspan}
	                });
					j = j+{$rowspan}-1;
	            }
			}
        })();
JS;
        return $output;
    }

    public function buildJsInitOptionsHead()
    {
        $options = parent::buildJsInitOptionsHead();
        
        // If we have multiple transposed columns, we must sort on the client to make sure, the transposed columns
        // are attached to their spanning columns and stay in exactly the same order. So we add a custom sorter to
        // the event fired when a user is about to sort a column.
        // NOTE: we can't switch to sorting on the client generally, because this won't work if the initial sorting
        // is done over a transposed column or a label column. And sorting over label column is what you mostly will
        // need to do to ensure a meaningfull order of the transposed values.
        if (count($this->getWidget()->getColumnsTransposed()) > 1) {
            $options .= <<<JS
				, onBeforeSortColumn: function(sort, order){
					var remoteSortSetting = $(this).datagrid('options').remoteSort;
					$(this).datagrid('options').remoteSort = false;
					if (!$(this).datagrid('options')._customSort){
						$(this).datagrid('options')._customSort = true;
						$(this).datagrid('sort', {
							sortName: sort+',_subRowIndex',
							sortOrder: order+',asc'
						});
						$(this).datagrid('options')._customSort = false;
						return false;
					}
					$(this).datagrid('options').remoteSort = remoteSortSetting;
				}
JS;
        }
        return $options;
    }

    public function buildJsEditModeEnabler()
    {
        $editable_transposed_cols = array();
        foreach ($this->getWidget()->getColumnsTransposed() as $pos => $col) {
            if ($col->isEditable()) {
                $editable_transposed_cols[] = $pos;
            }
        }
        $editable_transposed_cols = json_encode($editable_transposed_cols);
        return <<<JS
					var rows = $(this).{$this->getElementType()}("getRows");
					for (var i=0; i<rows.length; i++){
						if ({$editable_transposed_cols}.indexOf(rows[i]._subRowIndex) > -1){
							$(this).{$this->getElementType()}("beginEdit", i);
						}
					}
JS;
    }
}
?>