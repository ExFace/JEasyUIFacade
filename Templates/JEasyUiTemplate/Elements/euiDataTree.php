<?php
namespace exface\JEasyUiTemplate\Templates\JEasyUiTemplate\Elements;

use exface\Core\Widgets\DataTree;

/**
 * @method DataTree getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class euiDataTree extends euiDataTable
{

    private $on_expand = '';

    protected function init()
    {
        parent::init();
        $this->setElementType('treegrid');
    }

    public function buildJsInitOptionsHead()
    {
        if ($this->isEditable()) {
            $this->addOnExpand('
					if (row){
						var rows = $(this).' . $this->getElementType() . '("getChildren", row.' . $this->getWidget()->getUidColumn()->getDataColumnName() . ');
						for (var i=0; i<rows.length; i++){
							$(this).' . $this->getElementType() . '("beginEdit", rows[i].' . $this->getWidget()->getUidColumn()->getDataColumnName() . ');
						}
					}
					');
        }
        $grid_head = parent::buildJsInitOptionsHead() . '
                        , treeField: "' . $this->getWidget()->getTreeColumn()->getDataColumnName() . '"
                        , lines: false
                        ' . ($this->buildJsOnExpandScript() ? ', onExpand: function(row){' . $this->buildJsOnExpandScript() . '}' : '');
        
        return $grid_head;
    }

    public function prepareData(\exface\Core\Interfaces\DataSheets\DataSheetInterface $data_sheet)
    {
        $result = parent::prepareData($data_sheet);
        /* @var $widget \exface\Core\Widgets\DataTree */
        $widget = $this->getWidget();
        foreach ($result['rows'] as $nr => $row) {
            if ($row[$widget->getTreeFolderFlagAttributeAlias()]) {
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
            
            unset($result['rows'][$nr][$this->getWidget()->getTreeFolderFlagAttributeAlias()]);
            if ($result['rows'][$nr][$widget->getTreeParentIdAttributeAlias()] != $widget->getTreeRootUid()) {
                $result['rows'][$nr]['_parentId'] = $result['rows'][$nr][$widget->getTreeParentIdAttributeAlias()] ? $result['rows'][$nr][$widget->getTreeParentIdAttributeAlias()] : 0;
            }
        }
        
        $result['footer'][0][$this->getWidget()->getTreeColumn()->getDataColumnName()] = '';
        
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
                    var nodeId = {$js_var_param}['id'];
                    if (nodeId) {
                        if ({$js_var_param}['data'] !== undefined && {$js_var_param}['data']['filters'] !== undefined && {$js_var_param}['data']['filters']['conditions'] !== undefined) {
                            var conditions = {$js_var_param}['data']['filters']['conditions'];
                            for (var c in conditions) {
                                if (conditions[c]['expression'] == '{$this->getWidget()->getTreeParentIdAttributeAlias()}') {
                                    {$js_var_param}['data']['filters']['conditions'][c]['value'] = nodeId;
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
}
?>