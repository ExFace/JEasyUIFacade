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
    
    public function buildHtmlHeadTags()
    {
        $includes = parent::buildHtmlHeadTags();
        $includes[] = '<script type="text/javascript" src="exface/vendor/exface/JEasyUIFacade/Facades/js/jeasyui/extensions/treegrid-dnd/treegrid-dnd.js"></script>';
        return $includes;
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
        
        //Enable Drag and Drop
        $enableDnDJs= "console.log('Enable DragNDrop', data);$('#{$this->getId()}').{$this->getElementType()}('enableDnd', null);";
        //$this->addOnLoadSuccess($enableDnDJs);
        
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

    public static function buildResponseData(JEasyUIFacade $facade, DataSheetInterface $data_sheet, WidgetInterface $widget)
    {
        $result = $facade->buildResponseData($data_sheet);
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
            // We save references to all rows in an array indexed with row UIDs
            $rowsById[$row[$idCol]] =& $result['rows'][$nr];
        }
        
        // The jEasyUI treegrid cannot build trees itself, so we need to form a hierarchy here, if we have
        // parents and their children within our data.
        $rowCnt = count($result['rows']);
        for ($nr = $rowCnt-1; $nr >= 0; $nr--) {
            $row = $result['rows'][$nr];
            $parentId = $row[$parentCol];
            
            // Now, if the parent id is found in our array, we need to remove the row from the flat data array
            // and put it into the children-array of it's parent row. We need to use references here as the
            // next row may be a child of one of the children in-turn.
            if ($rowsById[$parentId] !== null) {                
                if ($rowsById[$parentId]['children'] === null) {
                    //add children array to parent, add row as child
                    $rowsById[$parentId]['children'][] =& $result['rows'][$nr];;
                } else {
                    //add row as the first object in children array to parent
                    $val =& $result['rows'][$nr];
                    array_unshift($rowsById[$parentId]['children'],  $val);
                }
                    $rowsById[$parentId]['state'] = 'open';
                    //set new reference for the row, as current reference will be unset
                    $rowsById[$row[$idCol]] =& $rowsById[$parentId]['children'][0];
                unset ($result['rows'][$nr]);
            }
        }
        
        // Get rid of gaps in row numbers
        $result['rows'] = array_values($result['rows']);
        
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
                    console.log({$js_var_param});
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
                    } else {                        
                        var treeData = $('#{$this->getId()}').{$this->getElementType()}('getData');
                        console.log('TreeData: ', treeData);
                        (function (){
                            function addNode(node) {
                                if ({$js_var_param}['data'] !== undefined && {$js_var_param}['data']['filters'] !== undefined && {$js_var_param}['data']['filters']['conditions'] !== undefined) {
                                    var conditions = {$js_var_param}['data']['filters']['conditions'];
                                    for (var c in conditions) {
                                        if (conditions[c]['expression'] == '{$this->getWidget()->getTreeParentIdAttributeAlias()}') {
                                            if (node['children'] !== undefined && node['state'] === 'open') {
                                                var oldValue = {$js_var_param}['data']['filters']['conditions'][c]['value'];
                                                {$js_var_param}['data']['filters']['conditions'][c]['value'] = oldValue + ',' + node['{$this->getWidget()->getTreeFolderFilterColumn()->getDataColumnName()}'];
                                            }
                                        }
                                    }
                                }
                                if (node['children'] !== undefined && node['state'] === 'open') {
                                    var children = node['children'];
                                    children.forEach(function (child) {
                                        if (child['children'] !== undefined && child['state'] === 'open') {
                                            addNode(child);
                                        }
                                    });
                                }
                                return null;
                            }                            
                            if (Array.isArray(treeData) && treeData.length > 0) {
                                if ({$js_var_param}['data'] !== undefined && {$js_var_param}['data']['filters'] !== undefined && {$js_var_param}['data']['filters']['conditions'] !== undefined) {
                                    var conditions = {$js_var_param}['data']['filters']['conditions'];
                                    for (var c in conditions) {
                                        if (conditions[c]['expression'] == '{$this->getWidget()->getTreeParentIdAttributeAlias()}') {
                                            var oldValue = {$js_var_param}['data']['filters']['conditions'][c]['value'];
                                            if (oldValue === '' || oldValue === undefined || oldValue === null) {
                                                {$js_var_param}['data']['filters']['conditions'][c]['value'] = {$this->getWidget()->getTreeRootUid()};
                                            }                                           
                                        }
                                    }
                                }
                                treeData.forEach(function (node) {
                                    if (node['children'] !== undefined && node['state'] === 'open') {
                                        addNode(node);
                                    }
                                });
                            }
                        })();
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