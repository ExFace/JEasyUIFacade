<?php
namespace exface\JEasyUiTemplate\Templates\JEasyUiTemplate\Elements;

class euiPivotTable extends euiDataTable
{

    private $label_values = array();

    protected function init()
    {
        parent::init();
        $this->setElementType('pivotgrid');
        $this->addOnBeforeLoad('if (!$("#' . $this->getId() . '").data("layouted")) {$("#' . $this->getId() . '").data("layouted", 1)}');
    }

    function buildJs()
    {
        $widget = $this->getWidget();
        $output = '';
        
        // Prevent loading data again every time the pivot layout changes. The layout still works with the same data, so why load it again?
        // TODO This simple approach did not work, because the layout is not refreshed then. Need another approach somehow.
        /*
         * $this->addOnBeforeLoad("
         * console.log($(this).treegrid('getData'));
         * if ($(this).treegrid('getData').length > 0) return false;
         * ");
         */
        
        // add initial sorters
        $sort_by = array();
        $direction = array();
        if (count($widget->getSorters()) > 0) {
            foreach ($widget->getSorters() as $sort) {
                $sort_by[] = urlencode($sort->getProperty('attribute_alias'));
                $direction[] = urlencode($sort->getProperty('direction'));
            }
            $sortColumn = ", sortName: '" . implode(',', $sort_by) . "'";
            $sortOrder = ", sortOrder: '" . implode(',', $direction) . "'";
        }
        
        // get the standard params for grids
        $grid_head = $this->buildJsDataSource();
        $grid_head .= $sortColumn . $sortOrder . ($this->buildJsOnBeforeLoadFunction() ? ', onBeforeLoad: ' . $this->buildJsOnBeforeLoadFunction() : '') . '
						, toolbar:[ {
					        text:\'Layout\',
					        handler:function(){
					            $(\'#' . $this->getId() . '\').pivotgrid(\'layout\');
					        }
					    } ]
					    , fit: true
						, pivot: {rows: [], columns: [], values: []}';
        
        // instantiate the data grid
        $output .= '$("#' . $this->getId() . '").' . $this->getElementType() . '({' . $grid_head . '});';
        
        return $output;
    }

    function buildHtmlHeadTags()
    {
        $headers = parent::buildHtmlHeadTags();
        $headers[] = '<script type="text/javascript" src="exface/vendor/exface/JEasyUiTemplate/Templates/JEasyUiTemplate/js/jeasyui/extensions/pivotgrid/jquery.pivotgrid.js"></script>';
        return $headers;
    }

    /**
     * A pivotGrid expects data in a different format: [ {field: value, ...}, {...}, ...
     * ]
     *
     * @see \exface\JEasyUiTemplate\Templates\JEasyUiTemplate\Elements\jeasyuiAbstractWidget::prepareData()
     */
    public function prepareData(\exface\Core\Interfaces\DataSheets\DataSheetInterface $data_sheet)
    {
        // apply the formatters
        foreach ($data_sheet->getColumns() as $name => $col) {
            if ($formatter = $col->getFormatter()) {
                $expr = $formatter->toString();
                $function = substr($expr, 1, strpos($expr, '(') - 1);
                $formatter_class_name = 'formatters\'' . $function;
                if (class_exists($class_name)) {
                    $formatter = new $class_name($y);
                }
                $data_sheet->setColumnValues($name, $formatter->evaluate($data_sheet, $name));
            }
        }
        $data = array();
        foreach ($data_sheet->getRows() as $row_nr => $row) {
            foreach ($row as $fld => $val) {
                if ($col = $this->getWidget()->getColumnByDataColumnName($fld)) {
                    $data[$row_nr][$col->getCaption()] = $val;
                }
            }
        }
        return $data;
    }

    public function buildJsDataSource()
    {
        $result = parent::buildJsDataSource();
        
        $result = substr($result, 0, - 1);
        // $result .= ', ' . $this->getTemplate()->getUrlFilterPrefix() . $this->getMetaObject()->getUidAttributeAlias() . ': ($("#' . $this->getId() . '").data("layouted") ? "" : -1)}';
        $result .= ', page: (!$("#' . $this->getId() . '").data("layouted") ? "" : 1), rows: (!$("#' . $this->getId() . '").data("layouted") ? "" : 1)}';
        
        return $result;
    }
}
?>