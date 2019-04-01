<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\DataTree;
use exface\Core\Interfaces\Actions\ActionInterface;
use exface\JEasyUIFacade\Facades\JEasyUIFacade;
use exface\Core\Interfaces\DataSheets\DataSheetInterface;
use exface\Core\Interfaces\WidgetInterface;

/**
 * @method DataTree getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class EuiDataTree extends EuiDataTable
{

    private $on_expand = '';

    protected function init()
    {
        parent::init();
        $this->setElementType('treegrid');
        
        if ($this->getWidget()->getTreeLeafIdColumnId() !== null) {
            $leafIdCol = $this->getWidget()->getColumn($this->getWidget()->getTreeLeafIdColumnId());
            if (! $leafIdCol->getDataColumnName()) {
                $leafIdCol->setDataColumnName('_leafId');
            }
        }
    }

    public function buildJsInitOptionsHead()
    {        
        $widget = $this->getWidget();
        $leafIdColumnName = $widget->getTreeLeafIdColumn()->getDataColumnName();
        
        if ($this->isEditable()) {
            $this->addOnExpand('
					if (row){
						var rows = $(this).' . $this->getElementType() . '("getChildren", row.' . $leafIdColumnName . ');
						for (var i=0; i<rows.length; i++){
							$(this).' . $this->getElementType() . '("beginEdit", rows[i].' . $leafIdColumnName . ');
						}
					}
					');
        }
        
        if (($leafIdDelim = $widget->getTreeLeafIdConcatenate()) !== null) {
            $calculatedIdField = ', idField: "_leafId"';
            $leafIdCalcScript = 'data.rows[row]["_leafId"] = (parentId ? parentId+"' . $leafIdDelim . '" : "")+data.rows[row]["' . $widget->getUidColumn()->getDataColumnName() . '"];';
        }
        
        $grid_head = parent::buildJsInitOptionsHead() . $calculatedIdField . '
                        , treeField: "' . $widget->getTreeColumn()->getDataColumnName() . '"
                        , lines: false
                        , loadFilter: function(data, parentId) {
                            
                            var row = 0;
                            var rowCnt = data.rows.length;
                            var field, parentRow;
                            
                            for (row=0; row<rowCnt; row++) {
                                if (parentId !== null) {
                                    data.rows[row]["_parentId"] = parentId;
                                }
                                ' . $leafIdCalcScript . '
                            }

                            return data;
                        }
                        ' . $this->buildJsOnLoadSuccessOption() . '
                        ' . ($this->buildJsOnExpandScript() ? ', onExpand: function(row){' . $this->buildJsOnExpandScript() . '}' : '');
        
        return $grid_head;
    }

    public static function buildResponseData(JEasyUIFacade $template, DataSheetInterface $data_sheet, WidgetInterface $widget)
    {
        $result = $template->buildResponseData($data_sheet);
        /* @var $widget \exface\Core\Widgets\DataTree */
        $folderFlagCol = $widget->hasTreeFolderFlag() ? $widget->getTreeFolderFlagAttributeAlias() : null;
        $parentCol = $widget->getTreeParentIdAttributeAlias();
        $idCol = $widget->getUidColumn()->getDataColumnName();
        $rowsById = [];
        foreach ($result['rows'] as $nr => $row) {
            // If we know, which attribute flags a leaf as a folder, use it to set the node state (open/close)
            if ($folderFlagCol !== null) {
                if ($row[$folderFlagCol]) {
                    // $result['rows'][$nr]['state'] = $row[$this->getWidget()->getTreeFolderFlagAttributeAlias()] ? 'closed' : 'open';
                    $result['rows'][$nr]['state'] = 'closed';
                    // Dirty hack to remove zero numeric values on folders, because they are easily assumed to be sums
                    foreach ($row as $fld => $val) {
                        if (is_numeric($val) && intval($val) == 0) {
                            $result['rows'][$nr][$fld] = '';
                        }
                    }
                } else {
                    $result['rows'][$nr]['state'] = 'open';
                }
                
                unset($result['rows'][$nr][$folderFlagCol]);
            } else {
                // If we can't tell, if a node has children - make it close (assume it may have children)
                $result['rows'][$nr]['state'] = 'closed';
            }
            
            // The jEasyUI treegrid cannot build trees itself, so we need to form a hierarchy here, if we have
            // parents and their children within our data.
            $parentId = $row[$parentCol];
            // We save references to all rows in an array indexed with row UIDs
            $rowsById[$row[$idCol]] =& $result['rows'][$nr];
            // Now, if the parent id is found in our array, we need to remove the row from the flat data array
            // and put it into the children-array of it's parent row. We need to use references here as the
            // next row may be a child of one of the children in-turn.
            // TODO This will probably only work well if the initial rows are in the correct order...
            if ($rowsById[$parentId] !== null) {
                $rowsById[$parentId]['children'][] =& $result['rows'][$nr];
                $rowsById[$parentId]['state'] = 'open';
                unset ($result['rows'][$nr]);
            }
        }
        
        $result['footer'][0][$widget->getTreeColumn()->getDataColumnName()] = '';
        
        return $result;
    }

    public function buildJsEditModeEnabler()
    {
        return '
					var rows = $(this).' . $this->getElementType() . '("getRoots");
					for (var i=0; i<rows.length; i++){
						$(this).' . $this->getElementType() . '("beginEdit", rows[i].' . $this->getWidget()->getUidColumn()->getDataColumnName() . ');
					}
				';
    }

    protected function addOnExpand($script)
    {
        $this->on_expand .= $script;
    }

    protected function buildJsOnExpandScript()
    {
        return $this->on_expand;
    }
    
    protected function buildJsOnBeforeLoadScript($js_var_param = 'param', $js_var_row = 'row')
    {
        return parent::buildJsOnBeforeLoadScript($js_var_param) . <<<JS
                    
                    // Make parentId a regular filter instead of an extra URL parameter
                    var parentId = {$js_var_param}['id'];
                    if (parentId) {
                        if ({$js_var_param}['data'] !== undefined && {$js_var_param}['data']['filters'] !== undefined && {$js_var_param}['data']['filters']['conditions'] !== undefined) {
                            var conditions = {$js_var_param}['data']['filters']['conditions'];
                            for (var c in conditions) {
                                if (conditions[c]['expression'] == '{$this->getWidget()->getTreeParentIdAttributeAlias()}') {
                                    {$js_var_param}['data']['filters']['conditions'][c]['value'] = row['{$this->getWidget()->getTreeFolderFilterColumn()->getDataColumnName()}'];
                                }
                            }
                        }
                        delete {$js_var_param}['id'];
                    }

JS;
    }
    
    protected function buildJsOnBeforeLoadFunction()
    {
        if (! $this->buildJsOnBeforeLoadScript()) {
            return '';
        }
        
        return <<<JS
        
                function(row, param) {
    				{$this->buildJsOnBeforeLoadScript('param', 'row')}
				}
				
JS;
    }
    				
    public function buildJsDataGetter(ActionInterface $action = null)
    {
        $parentData = parent::buildJsDataGetter($action);
        // TODO #nested-data-sheets instead of removing children, replace the key by the alias of the relation to the child object (once nested sheets are supported)
        return <<<JS
function() {
    var data = {$parentData};
    for (var i in data.rows) {
        delete data.rows[i]['children'];
        delete data.rows[i]['state'];
    }
    return data;
}()
JS;
    }
}