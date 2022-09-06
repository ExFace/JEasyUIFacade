<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\DataColumnTransposed;
use exface\Core\DataTypes\AggregatorFunctionsDataType;
use exface\Core\Factories\DataTypeFactory;

/**
 *
 * @method exface\Core\Widgets\DataMatrix getWidget()
 *        
 * @author aka
 *        
 */
class EuiDataMatrix extends EuiDataTable
{

    private $label_values = array();

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
        return parent::getLoadFilterScript($dataJs) . $this->buildJsTransposeColumns($this->buildJsInitPager());
    }

    /**
     * @return string
     */
    protected function buildJsTransposeColumns(string $onTransposedJs = '') : string
    {
        $visible_cols = array();
        $data_cols = array();
        $data_cols_totals = array();
        $label_cols = array();
        $formatters = [];
        $stylers = [];
        $widget = $this->getWidget();
        foreach ($widget->getColumns() as $col) {
            if ($col instanceof DataColumnTransposed) {
                $data_cols[] = $col->getDataColumnName();
                $label_cols[$col->getLabelAttributeAlias()][] = $col->getDataColumnName();
                if ($col->hasFooter() === true && $col->getFooter()->hasAggregator() === true) {
                    $data_cols_totals[$col->getDataColumnName()] = $col->getFooter()->getAggregator()->exportString();
                }
                $cellElem = $this->getFacade()->getElement($col->getCellWidget());
                $formatters[$col->getDataColumnName()] = 'function(value){return ' . $cellElem->buildJsValueDecorator('value') . '}'; 
                $stylers[$col->getDataColumnName()] = $this->buildJsInitOptionsColumnStyler($col, 'value', 'oRow', 'iRowIdx', 'null');
                $labelCol = $widget->getColumnByAttributeAlias($col->getLabelAttributeAlias());
                $formatters[$labelCol->getDataColumnName()] = 'function(value){return ' . $this->getFacade()->getDataTypeFormatter($labelCol->getDataType())->buildJsFormatter('value') . '}'; 
            } elseif (! $col->isHidden()) {
                $visible_cols[] = $col->getDataColumnName();
            } 
        }
        $visible_cols = "'" . implode("','", $visible_cols) . "'";
        $data_cols = "'" . implode("','", $data_cols) . "'";
        $label_cols = json_encode($label_cols);
        $data_cols_totals = json_encode($data_cols_totals);
        $aggr_function_type = DataTypeFactory::createFromPrototype($this->getWorkbench(), AggregatorFunctionsDataType::class);
        $aggr_names = json_encode($aggr_function_type->getLabels());
        
        foreach ($formatters as $fld => $fmt) {
            $formattersJs .= '"' . $fld . '": ' . $fmt . ',';
        }
        $formattersJs = '{' . $formattersJs . '}';
        
        foreach ($stylers as $fld => $fmt) {
            $stylersJs .= '"' . $fld . '": ' . $fmt . ',';
        }
        $stylersJs = '{' . $stylersJs . '}';
        
        $transpose_js = <<<JS
        
$("#{$this->getId()}").data("_skipNextLoad", true);

var jqSelf = $(this);

var oTransposed = (function(oData) {
    var oResult = {
        bTransposed: false,
        oDataOriginal: oData
    };
    var aDataCols = [ {$data_cols} ]; // [transpColName1, transpColName2, ...]
    var aVisibleCols = [ {$visible_cols} ];
    var oDataColsTotals = {$data_cols_totals}; // {transpColName2: SUM, ...}
    var oAggrLabels = $aggr_names; // {SUM: 'Sum', ...}
    var oLabelCols = {$label_cols}; // {labelAttrAlias1: [transpColName1, transpColName2], labelAttrAlias2: [...], ...}
    var iFrozenColCnt = {$widget->getFreezeColumns()};
    var aRows = oData.rows;
    var aColsOrig = jqSelf.data('_columnsBkp'); // [col1, col2, ...]
    var aColsNew = []; // [col1, col2, ...]
    var aColsNewFrozen = []; // [col1, col2, ...]
    var oColsTransposed = {};
    var iColsTransposedCnt = 0;
    var oFormatters = $formattersJs; // [data_column_name: function_formatting_values]
    var oStylers = $stylersJs; // [data_column_name => function_returning_CSS_styles]
    
	var aRowsNew = [];
	var oRowKeys = {};
    var oFooterRows = {};
    
    if (! aColsOrig) {
        aColsOrig = jqSelf.datagrid('options').columns;
        if (iFrozenColCnt > 0) {
            for (var fi = jqSelf.datagrid('options').frozenColumns[0].length-1; fi >= 0; fi--) {
                aColsOrig[0].unshift(jqSelf.datagrid('options').frozenColumns[0][fi]);
            }
        }
        jqSelf.data('_columnsBkp', aColsOrig);
    }
    
    for (var i=0; i<aColsOrig.length; i++){
    	var newColRow = [];
    	for (var j=0; j<aColsOrig[i].length; j++){
    		var fld = aColsOrig[i][j].field;
    		if (aDataCols.indexOf(fld) > -1){
    			oData.transposed = 0;
    			oColsTransposed[fld] = {
    				column: aColsOrig[i][j]
                    /* TODO add/remove properties if needed?!
                    ,
    				subRowIndex: iColsTransposedCnt++,
    				colIndex: j*/
    			};
    		} else if (oLabelCols[fld] != undefined) {
    			// Add a subtitle column to show a caption for each subrow if there are multiple
    			if (aDataCols.length > 1){
    				var newCol = {
        				field: '_subRowIndex',
        				title: '',
        				align: 'right',
        				sortable: false,
        				hidden: true
                    }
    				newColRow.push(newCol);
    				
    				var newCol = $.extend(true, {}, aColsOrig[i][j], {
                        field: fld+'_subtitle',
        				title: '',
        				align: 'right',
        				sortable: false,
                        formatter: false,
                        styler: false
                    });
    				newColRow.push(newCol);
    			}
    			// Create a column for each value if the label column
    			var labels = [];
    			for (var l=0; l<aRows.length; l++){
    				if (labels.indexOf(aRows[l][fld]) == -1){
    					labels.push(aRows[l][fld]);
    				}
    			}
    			for (var l=0; l<labels.length; l++){
    				let label = labels[l];
    					if (typeof label !== 'string') {
    						label = String(label);
    					}
    					label = label.replaceAll('-', '_').replaceAll(':', '_');
    				var newCol = $.extend(true, {}, aColsOrig[i][j], {
                        field: label,
        				title: '<span title="'+$(aColsOrig[i][j].title).text()+' '+labels[l]+'">'+(oFormatters[fld] ? oFormatters[fld](labels[l]) : labels[l])+'</title>',
        				_transposedFields: oLabelCols[fld],
        				sortable: false, // No header sorting (not clear, what to sort!)
                        formatter: false,
                        styler: false
                    });
    				newColRow.push(newCol);
    			}
    			// Create a totals column if there are totals
                // The footer of the totals column will contain the overall total provided by the server
    			if (oDataColsTotals !== {}){
    				var totals = [];
    				for (var tfld in oDataColsTotals){
    					var tfunc = oDataColsTotals[tfld];
    					if (totals.indexOf(tfunc) == -1){
    						var newCol = $.extend(true, {}, aColsOrig[i][j]);
    						newCol.field = fld+'_'+tfunc;
    						newCol.title = oAggrLabels[tfunc];
    						newCol.align = 'right';
    						newCol.sortable = false;
    						newColRow.push(newCol);
    						totals.push(tfunc);
                            oData.footer[0][newCol.field] = oData.footer[0][tfld];
    					}
    				}
    			}
    		} else {
    			newColRow.push(aColsOrig[i][j]);
    		}
    	}
    	for (var i in oColsTransposed){
    		if (oColsTransposed[i].column.editor != undefined){
    			for (var j=0; j<newColRow.length; j++){
    				if (newColRow[j]._transposedFields != undefined && newColRow[j]._transposedFields.indexOf(i) > -1){
    					newColRow[j].editor = oColsTransposed[i].column.editor;
    				}
    			}
    		}
    	}
    	
        if (iFrozenColCnt > 0) {
            aColsNewFrozen.push([]);
            for (var i = 0; i < newColRow.length; i++) {
                if (newColRow[i].hidden !== true && i < iFrozenColCnt) {
                    aColsNewFrozen[0].push(newColRow[i]);
                    newColRow.splice(i, 1);
                }
            }
        }
    	aColsNew.push(newColRow);
    }
    
    if (oData.transposed === 0){
    	for (var i=0; i<aRows.length; i++){
    		var newRowId = '';
    		var newRow = {};
    		var newColVals = {};
    		var newColId = '';
    		for (var fld in aRows[i]){
    			var val = aRows[i][fld];
    			if (oLabelCols[fld] != undefined){
    				if (typeof val !== 'string') {
    					val = String(val);
    				}
    				val = val.replaceAll('-', '_').replaceAll(':', '_');
    				newColId = val;
    				newColGroup = fld;
    			} else if (aDataCols.indexOf(fld) > -1){
    				newColVals[fld] = val;
    			} else if (aVisibleCols.indexOf(fld) > -1) {
    				newRowId += val;
    				newRow[fld] = val;
    			}
    			
    			// TODO save UID and other system attributes to some invisible data structure
    		}
    		
    		var subRowCounter = 0;
    		for (var fld in newColVals){
    			if (oRowKeys[newRowId+fld] == undefined){
    				oRowKeys[newRowId+fld] = $.extend(true, {}, newRow);
    				oRowKeys[newRowId+fld]['_subRowIndex'] = subRowCounter++;
    			}
    			oRowKeys[newRowId+fld][newColId] = oFormatters[fld] ? oFormatters[fld](newColVals[fld]) : newColVals[fld];
                if (oStylers[fld]) {
                    oRowKeys[newRowId+fld][newColId] = '<span style="' + oStylers[fld](newColVals[fld]) + '">' + oRowKeys[newRowId+fld][newColId] + '</span>';
                }
    			oRowKeys[newRowId+fld][newColGroup+'_subtitle'] = '<i style="' + (oStylers[fld] ? oStylers[fld]() : '') + '">'+oColsTransposed[fld].column.title+'</i>';
    			if (oDataColsTotals[fld] != undefined){
    				var newVal = parseFloat(newColVals[fld]);
    				var oldVal = oRowKeys[newRowId+fld][newColGroup+'_'+oDataColsTotals[fld]];
                    var oldTotal = (oData.footer[0][newColId] || 0);
    				oldVal = oldVal ? oldVal : 0;
    				switch (oDataColsTotals[fld]){
    					case 'SUM':
    						oRowKeys[newRowId+fld][newColGroup+'_'+oDataColsTotals[fld]] = oldVal + newVal;
                            if (aDataCols.length === 1){
                                oData.footer[0][newColId] = oldTotal + newVal;
                            }
    						break;
    					case 'MAX':
    						oRowKeys[newRowId+fld][newColGroup+'_'+oDataColsTotals[fld]] = oldVal < newVal ? newVal : oldVal;
                            if (aDataCols.length === 1){
                                oData.footer[0][newColId] = oldTotal < newVal ? newVal : oldTotal;
                            }
    						break;
    					case 'MIN':
    						oRowKeys[newRowId+fld][newColGroup+'_'+oDataColsTotals[fld]] = oldVal > newVal ? newVal : oldVal;
                            if (aDataCols.length === 1){
                                oData.footer[0][newColId] = oldTotal > newVal ? newVal : oldTotal;
                            }
    						break;
    					case 'COUNT':
    						oRowKeys[newRowId+fld][newColGroup+'_'+oDataColsTotals[fld]] = oldVal + 1;
                            if (aDataCols.length === 1){
                                oData.footer[0][newColId] = oldTotal + 1;
                            }
    						break;
    					// TODO add more totals
    				}
    			}
    		}
    	}
    	for (var i in oRowKeys){
    		aRowsNew.push(oRowKeys[i]);
    	}
    	
    	oData.rows = aRowsNew;
    	oData.transposed = 1;
        oResult.bTransposed = 1;
    
    	jqSelf.datagrid({frozenColumns: aColsNewFrozen, columns: aColsNew});

    }
    
    oResult.oDataTransposed = oData;

    return oResult;

})(data);    
        
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

			var fields = {$fields_to_merge};
			for (var i=0; i<fields.length; i++){
	            for(var j=0; j<$(this).datagrid('getRows').length; j++){
	                $(this).datagrid('mergeCells',{
	                    index: j,
	                    field: fields[i],
	                    rowspan: {$rowspan}
	                });
					j = j+{$rowspan}-1;
	            }
			}

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