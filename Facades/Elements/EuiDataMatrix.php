<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\DataColumnTransposed;
use exface\Core\Widgets\DataMatrix;

/**
 *
 * @method DataMatrix getWidget()
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
        $this->setElementType('datagrid');
        $this->buildJsTransposer();
        $this->addOnLoadSuccess($this->buildJsCellMerger());
    }

    protected function buildJsTransposer()
    {
        $visible_cols = array();
        $data_cols = array();
        $data_cols_totlas = array();
        $label_cols = array();
        $formatters = [];
        $stylers = [];
        $widget = $this->getWidget();
        foreach ($widget->getColumns() as $col) {
            if ($col instanceof DataColumnTransposed) {
                $data_cols[] = $col->getDataColumnName();
                $label_cols[$col->getLabelAttributeAlias()][] = $col->getDataColumnName();
                if ($col->hasFooter() === true && $col->getFooter()->hasAggregator() === true) {
                    $data_cols_totlas[$col->getDataColumnName()] = $col->getFooter()->getAggregator()->exportString();
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
        $data_cols_totlas = json_encode($data_cols_totlas);
        
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

var dataCols = [ {$data_cols} ];
var dataColsTotals = {$data_cols_totlas};
var labelCols = {$label_cols};
var freezeCols = {$widget->getFreezeColumns()};
var rows = data.rows;
var cols = $(this).data('_columnsBkp');
var colsNew = [];
var colsNewFrozen = [];
var colsTransposed = {};
var colsTranspCount = 0;
// data_column_name => formatter_callback
var formatters = $formattersJs;
// data_column_name => styler_callback returning CSS styles
var stylers = $stylersJs;

if (! cols) {
    cols = $(this).datagrid('options').columns;
    if (freezeCols > 0) {
        for (var fi = $(this).datagrid('options').frozenColumns[0].length-1; fi >= 0; fi--) {
            cols[0].unshift($(this).datagrid('options').frozenColumns[0][fi]);
        }
    }
    $(this).data('_columnsBkp', cols);
}

for (var i=0; i<cols.length; i++){
	var newColRow = [];
	for (var j=0; j<cols[i].length; j++){
		var fld = cols[i][j].field;
		if (dataCols.indexOf(fld) > -1){
			data.transposed = 0;
			colsTransposed[fld] = {
				column: cols[i][j],
				subRowIndex: colsTranspCount++,
				colIndex: j
			};
		} else if (labelCols[fld] != undefined) {
			// Add a subtitle column to show a caption for each subrow if there are multiple
			if (dataCols.length > 1){
				var newCol = {
    				field: '_subRowIndex',
    				title: '',
    				align: 'right',
    				sortable: false,
    				hidden: true
                }
				newColRow.push(newCol);

				var newCol = $.extend(true, {}, cols[i][j], {
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
			for (var l=0; l<rows.length; l++){
				if (labels.indexOf(rows[l][fld]) == -1){
					labels.push(rows[l][fld]);
				}
			}
			for (var l=0; l<labels.length; l++){
				var newCol = $.extend(true, {}, cols[i][j], {
                    field: labels[l].replaceAll('-', '_').replaceAll(':', '_'),
    				title: '<span title="'+$(cols[i][j].title).text()+' '+labels[l]+'">'+(formatters[fld] ? formatters[fld](labels[l]) : labels[l])+'</title>',
    				_transposedFields: labelCols[fld],
    				// No header sorting (not clear, what to sort!)
    				sortable: false,
                    formatter: false,
                    styler: false
                });
				newColRow.push(newCol);
			}
			// Create a totals column if there are totals
			if (dataColsTotals !== {}){
				var totals = [];
				for (var t in dataColsTotals){
					var tfunc = dataColsTotals[t];
					if (totals.indexOf(tfunc) == -1){
						var newCol = $.extend(true, {}, cols[i][j]);
						newCol.field = fld+'_'+tfunc;
						newCol.title = tfunc;
						newCol.align = 'right';
						if (dataCols.length > 1){
							newCol.sortable = false;
						}
						newColRow.push(newCol);
						totals.push(tfunc);
					}
				}
			}
		} else {
			newColRow.push(cols[i][j]);
		}
	}
	for (var i in colsTransposed){
		if (colsTransposed[i].column.editor != undefined){
			for (var j=0; j<newColRow.length; j++){
				if (newColRow[j]._transposedFields != undefined && newColRow[j]._transposedFields.indexOf(i) > -1){
					newColRow[j].editor = colsTransposed[i].column.editor;
				}
			}
		}
	}
    
    if (freezeCols > 0) {
        colsNewFrozen.push([]);
        for (var i = 0; i < newColRow.length; i++) {
            if (newColRow[i].hidden !== true && i < freezeCols) {
                colsNewFrozen[0].push(newColRow[i]);
                newColRow.splice(i, 1);
            }
        }
    }
	colsNew.push(newColRow);
}

if (data.transposed === 0){
	var newRows = [];
	var newRowsObj = {};
	var visibleCols = [ {$visible_cols} ];
	for (var i=0; i<rows.length; i++){
		var newRowId = '';
		var newRow = {};
		var newColVals = {};
		var newColId = '';
		for (var fld in rows[i]){
			var val = rows[i][fld];
			if (labelCols[fld] != undefined){
				newColId = val.replaceAll('-', '_').replaceAll(':', '_');
				newColGroup = fld;
			} else if (dataCols.indexOf(fld) > -1){
				newColVals[fld] = val; 
			} else if (visibleCols.indexOf(fld) > -1) {
				newRowId += val;
				newRow[fld] = val;
			}

			// TODO save UID and other system attributes to some invisible data structure 
		}

		var subRowCounter = 0;
		for (var fld in newColVals){
			if (newRowsObj[newRowId+fld] == undefined){
				newRowsObj[newRowId+fld] = $.extend(true, {}, newRow);
				newRowsObj[newRowId+fld]['_subRowIndex'] = subRowCounter++;
			}
			newRowsObj[newRowId+fld][newColId] = formatters[fld] ? formatters[fld](newColVals[fld]) : newColVals[fld];
            if (stylers[fld]) {
                newRowsObj[newRowId+fld][newColId] = '<span style="' + stylers[fld](newColVals[fld]) + '">' + newRowsObj[newRowId+fld][newColId] + '</span>';
            }
			newRowsObj[newRowId+fld][newColGroup+'_subtitle'] = '<i style="' + (stylers[fld] ? stylers[fld]() : '') + '">'+colsTransposed[fld].column.title+'</i>';
			if (dataColsTotals[fld] != undefined){
				var newVal = parseFloat(newColVals[fld]);
				var oldVal = newRowsObj[newRowId+fld][newColGroup+'_'+dataColsTotals[fld]];
				oldVal = oldVal ? oldVal : 0;
				switch (dataColsTotals[fld]){
					case 'SUM':
						newRowsObj[newRowId+fld][newColGroup+'_'+dataColsTotals[fld]] = oldVal + newVal; 
						break;
					case 'MAX':
						newRowsObj[newRowId+fld][newColGroup+'_'+dataColsTotals[fld]] = oldVal < newVal ? newVal : oldVal; 
						break;
					case 'MIN':
						newRowsObj[newRowId+fld][newColGroup+'_'+dataColsTotals[fld]] = oldVal > newVal ? newVal : oldVal; 
						break;
					case 'COUNT':
						newRowsObj[newRowId+fld][newColGroup+'_'+dataColsTotals[fld]] = oldVal + 1; 
						break;
					// TODO add more totals
				}
			}
		}
	}
	for (var i in newRowsObj){
		newRows.push(newRowsObj[i]);
	}

	data.rows = newRows;
	data.transposed = 1;
	$(this).datagrid({frozenColumns: colsNewFrozen, columns: colsNew});
}
	

return data;
				
JS;
        $this->addLoadFilterScript($transpose_js);
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