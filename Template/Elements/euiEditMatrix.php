<?php
namespace exface\JEasyUiTemplate\Template\Elements;

use exface\Core\DataTypes\BooleanDataType;
use exface\Core\DataTypes\DateDataType;
use exface\Core\DataTypes\PriceDataType;
use exface\Core\DataTypes\NumberDataType;
use exface\Core\Interfaces\DataTypes\DataTypeInterface;

class euiEditMatrix extends euiDataMatrixOld
{    
    private $label_values = array();

    protected function init()
    {
        parent::init();
        $this->setElementType('datagrid');
    }

    function generateHeaders()
    {
        // handsontable
        $includes = array(
            '<script src="exface/vendor/exface/JEasyUiTemplate/Template/js/handsontable-rulejs/bower_components/handsontable/dist/handsontable.full.min.js"></script>',
            '<link rel="stylesheet" media="screen" href="exface/vendor/exface/JEasyUiTemplate/Template/js/handsontable-rulejs/bower_components/handsontable/dist/handsontable.full.min.css">'
        );
        // formula suppoert
        if ($this->getWidget()->getFormulasEnabled()) {
            $includes[] = '<link rel="stylesheet" media="screen" href="exface/vendor/exface/JEasyUiTemplate/Template/js/handsontable-rulejs/src/handsontable.formula.css">';
            $includes[] = '<script type="text/javascript" src="exface/vendor/exface/JEasyUiTemplate/Template/js/handsontable-rulejs/bower_components/ruleJS/dist/full/ruleJS.all.full.min.js"></script>';
            $includes[] = '<script type="text/javascript" src="exface/vendor/exface/JEasyUiTemplate/Template/js/handsontable-rulejs/src/handsontable.formula.js"></script>';
            
            $this->getWidget()->setShowRowNumbers(true);
        }
        // masonry for filter alignment
        $includes[] = '<script type="text/javascript" src="exface/vendor/bower-asset/masonry/dist/masonry.pkgd.min.js"></script>';
        return $includes;
    }

    function generateHtml()
    {
        $widget = $this->getWidget();
        $output = '';
        
        // add filters
        if ($widget->hasFilters()) {
            foreach ($widget->getFilters() as $fltr) {
                $fltr_html .= $this->getTemplate()->generateHtml($fltr);
            }
            
            $fltr_html .= <<<HTML

<div id="{$this->getId()}_sizer" style="width:calc(100%/{$this->getNumberOfColumns()});min-width:{$this->getWidthMinimum()}px;"></div>
HTML;
        }
        
        // add buttons
        $button_html = $this->buildHtmlButtons();
        
        // create a container for the toolbar
        if (! $widget->getHideHeader() && ($widget->hasFilters() || $widget->hasButtons())) {
            $output .= '<div id="' . $this->getToolbarId() . '" class="datagrid-toolbar">';
            if ($fltr_html) {
                $output .= '<div class="datagrid-filters">' . $fltr_html . '</div>';
            }
            $output .= '<div style="min-height: 30px;">';
            if ($button_html) {
                $output .= $button_html;
            }
            $output .= '<a style="position: absolute; right: 0; margin: 0 4px;" href="#" class="easyui-linkbutton" iconCls="fa fa-search" onclick="' . $this->buildJsFunctionPrefix() . 'doSearch()">' . $this->translate('WIDGET.SEARCH') . '</a></div>';
            $output .= '</div>';
        }
        // now the table itself
        $output .= '<div id="' . $this->getId() . '"></div>';
        return $output;
    }

    function generateJs()
    {
        $widget = $this->getWidget();
        $output = '			
			$("#' . $this->getId() . '").handsontable({
              ' . $this->buildJsInitOptionsHead() . '
            });
				';
        
        // doSearch function for the filters
        if ($widget->hasFilters()) {
            foreach ($widget->getFilters() as $fnr => $fltr) {
                $fltr_impl = $this->getTemplate()->getElement($fltr);
                $output .= $fltr_impl->generateJs();
                $fltrs[] = '"fltr' . str_pad($fnr, 2, 0, STR_PAD_LEFT) . '_' . urlencode($fltr->getAttributeAlias()) . '": ' . $fltr_impl->buildJsValueGetter();
            }
            // build JS for the search function
            $output .= '
						function ' . $this->buildJsFunctionPrefix() . 'doSearch(){
							$("#' . $this->getId() . '").' . $this->getElementType() . '("load",{' . implode(', ', $fltrs) . ', resource: "' . $widget->getPage()->getAliasWithNamespace() . '", element: "' . $this->getWidget()->getId() . '"});
						}';
        }
        
        return $output;
    }

    public function buildJsInitOptionsHead()
    {
        $widget = $this->getWidget();
        
        $output = $this->buildJsDataSource() . ', columnSorting: true' . ', sortIndicator: true' . ', manualColumnResize: true' . ', manualColumnMove: true' . 
        // . ', stretchH: "all"'
        ($widget->getShowRowNumbers() ? ', rowHeaders: true' : '') . ($widget->getFormulasEnabled() ? ', formulas: true' : '') . ($this->getWidth() ? ', width: ' . $this->getWidth() : '') . ($widget->getCaption() ? ', title: "' . str_replace('"', '\"', $widget->getCaption()) . '"' : '') . ', ' . $this->buildJsInitOptionsColumns();
        return $output;
    }

    /**
     * This special column renderer for the matrix replaces the column specified by label_column_id with a set of new columns for
     * every unique value in the column specified by data_column_id.
     * The new columns retain most properties of the replaced label column.
     *
     * @see \exface\JEasyUiTemplate\Template\Elements\grid::buildJsInitOptionsColumns()
     */
    public function buildJsInitOptionsColumns(array $cols = null)
    {
        $widget = $this->getWidget();
        $output = '';
        if (! $cols) {
            $cols = $this->getWidget()->getColumns();
        }
        $column_counter = 0;
        $headers = array();
        $columns = array();
        foreach ($cols as $col) {
            if ($col->getId() == $widget->getLabelColumnId()) {
                foreach ($this->label_values as $val) {
                    $headers[] = $this->renderColumnName($column_counter, $val);
                    $column_counter ++;
                }
            } elseif ($col->getId() == $widget->getDataColumnId()) {
                foreach ($this->label_values as $val) {
                    $column_name = \exface\Core\CommonLogic\DataSheets\DataColumn::sanitizeColumnName($val);
                    $columns[] = '{data: "' . $column_name . '", ' . $this->renderDataType($col->getDataType()) . '}';
                }
            } else {
                $headers[] = $this->renderColumnName($column_counter, $col->getCaption());
                $columns[] = '{data: "' . $col->getDataColumnName() . '", readOnly: true}';
            }
            $column_counter ++;
        }
        
        $output = '
				  colHeaders: ["' . implode('","', $headers) . '"]
				, columns: [' . implode(',', $columns) . ']
				';
        
        return $output;
    }

    protected function renderColumnName($column_number, $name)
    {
        if (! $this->getWidget()->getFormulasEnabled())
            return $name;
        $column_letters = array(
            'A',
            'B',
            'C',
            'D',
            'E',
            'F',
            'G',
            'H',
            'I',
            'J',
            'K',
            'L',
            'M',
            'N',
            'O',
            'P',
            'Q',
            'R',
            'S',
            'T',
            'U',
            'V',
            'W',
            'X',
            'Y',
            'Z'
        );
        return $name . ' (' . $column_letters[$column_number] . ')';
    }

    /**
     * This special data source renderer fetches data according to the filters an reorganizes the rows and column to fit the matrix.
     * It basically transposes the data column (data_column_id) using values of the label column (label_column_id) as new column headers.
     * The other columns remain untouched.
     *
     * @see \exface\JEasyUiTemplate\Template\Elements\grid::buildJsDataSource()
     */
    public function buildJsDataSource()
    {
        $widget = $this->getWidget();
        $visible_columns = array();
        $output = '';
        $result = array();
        
        // create data sheet to fetch data
        $ds = $this->getTemplate()->getWorkbench()->data()->createDataSheet($this->getMetaObject());
        // add columns
        foreach ($widget->getColumns() as $col) {
            $ds->getColumns()->addFromExpression($col->getAttributeAlias(), $col->getDataColumnName(), $col->isHidden());
            if (! $col->isHidden())
                $visible_columns[] = $col->getDataColumnName();
        }
        // add the filters
        foreach ($widget->getFilters() as $fw) {
            if (! is_null($fw->getValue())) {
                $ds->addFilterFromString($fw->getAttributeAlias(), $fw->getValue());
            }
        }
        // add the sorters
        foreach ($widget->getSorters() as $sort) {
            $ds->getSorters()->addFromString($sort->getProperty('attribute_alias'), $sort->getProperty('direction'));
        }
        // add aggregators
        foreach ($widget->getAggregations() as $aggr) {
            $ds->getAggregations()->addFromString($aggr);
        }
        
        // get the data
        $ds->dataRead();
        $label_col = $widget->getLabelColumn();
        $data_col = $widget->getDataColumn();
        foreach ($ds->getRows() as $nr => $row) {
            $new_row_id = null;
            $new_row = array();
            $new_col_val = null;
            $new_col_id = null;
            foreach ($row as $fld => $val) {
                
                if ($fld === $label_col->getDataColumnName()) {
                    $new_col_id = $val;
                    // TODO we probably need a special parameter for sorting labels!
                    if (! in_array($val, $this->label_values))
                        $this->label_values[] = $val;
                } elseif ($fld === $data_col->getDataColumnName()) {
                    $new_col_val = $val;
                } elseif (in_array($fld, $visible_columns)) {
                    $new_row_id .= $val;
                    $new_row[$fld] = $val;
                }
            }
            if (! is_array($result[$new_row_id])) {
                $result[$new_row_id] = $new_row;
            }
            $result[$new_row_id][$new_col_id] = $new_col_val;
        }
        
        $output = "data: [";
        foreach ($result as $row) {
            $output .= "{";
            foreach ($row as $fld => $val) {
                $val = str_replace('"', '\"', $val);
                if (! is_numeric($val)) {
                    $val = '"' . $val . '"';
                }
                $output .= '"' . $this->cleanId($fld) . '": ' . $val . ',';
            }
            $output = substr($output, 0, - 1);
            $output .= '},';
        }
        $output = substr($output, 0, - 1);
        $output .= ']';
        
        return $output;
    }

    public function renderDataType(DataTypeInterface $data_type)
    {
        if ($data_type instanceof BooleanDataType) {
            return 'type: "checkbox"';
        } elseif ($data_type instanceof DateDataType) {
            return 'type: "date"';
        } elseif ($data_type instanceof PriceDataType) {
            return 'type: "numeric", format: "0.00"';
        } elseif ($data_type instanceof NumberDataType) {
            return 'type: "numeric"';
        } else {
            return 'type: "text"';
        }
    }
}
?>