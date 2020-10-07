<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\DataTable;
use exface\Core\Interfaces\Actions\ActionInterface;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryDataTableTrait;
use exface\Core\Interfaces\Actions\iReadData;
use exface\Core\Widgets\DataColumn;
use exface\Core\Widgets\MenuButton;
use exface\Core\Factories\WidgetFactory;
use exface\Core\CommonLogic\Constants\Icons;
use exface\Core\CommonLogic\UxonObject;
use exface\Core\Exceptions\Facades\FacadeLogicError;
use exface\Core\Widgets\DataButton;
use exface\Core\Exceptions\Facades\FacadeOutputError;
use exface\Core\Exceptions\Facades\FacadeRuntimeError;
use exface\Core\Exceptions\Widgets\WidgetLogicError;

/**
 *
 * @author Andrej Kabachnik
 *        
 * @method DataTable getWidget()
 *        
 */
class EuiDataTable extends EuiData
{
    use JqueryDataTableTrait;
    
    private $collapseConfiguratorButton = null;

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiData::init()
     */
    protected function init()
    {
        parent::init();
        $this->setElementType('datagrid');
        $widget = $this->getWidget();
        
        // Take care of refresh links
        if ($refresh_link = $widget->getRefreshWithWidget()) {
            if ($refresh_link_element = $this->getFacade()->getElement($refresh_link->getTargetWidget())) {
                $refresh_link_element->addOnChangeScript($this->buildJsRefresh());
            }
        }
        
        // Initialize editors
        /* @var $col \exface\Core\Widgets\DataColumn */
        foreach ($widget->getColumns() as $col) {
            if ($col->isEditable()) {
                $editor = $this->getFacade()->getElement($col->getCellWidget());
                $this->setEditable(true);
                $this->editors[$col->getId()] = $editor;
            }
        }
        
        // If GroupView is used, make the group-by-column hidden. Otherwise the column layout gets broken!
        if ($widget->hasRowGroups() === true) {
            $widget->getRowGrouper()->getGroupByColumn()->setHidden(true);
        }
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiData::buildHtml()
     */
    public function buildHtml()
    {
        $widget = $this->getWidget();
        
        if ($widget->getHideHeader()){
            $header_style = 'visibility: hidden; height: 0px; padding: 0px;';
        } else {
            // Add header collapse button to the toolbar
            $searchBtnGroup = $widget->getToolbarMain()->getButtonGroupForSearchActions();
            $collapseButtonId = $this->getId() . '_headerCollapseButton';
            $collapseButton = WidgetFactory::createFromUxon($widget->getPage(), new UxonObject([
                'widget_type' => 'Button',
                'id' => $collapseButtonId,
                'action' => [
                    'alias' => 'exface.Core.CustomFacadeScript',
                    'script' => $this->buildJsFunctionPrefix() . '_toggleHeader();'
                ],
                'icon' => $widget->getConfiguratorWidget()->isCollapsed() === true ? Icons::CHEVRON_DOWN : Icons::CHEVRON_UP,
                'caption' => $this->translate('WIDGET.DATATABLE.CONFIGURATOR_EXPAND_COLLAPSE'),
                'align' => 'right',
                'hide_caption' => true
            ]), $searchBtnGroup);
            $searchBtnGroup->addButton($collapseButton,0);
        }
        
        $output .= <<<HTML
            <{$this->getBaseHtmlElement()} id="{$this->getId()}"></{$this->getBaseHtmlElement()}>
            <div id="{$this->getToolbarId()}" style="{$header_style}">
                {$this->buildHtmlTableHeader()}
            </div>
HTML;
        
        return $this->buildHtmlGridItemWrapper($output);
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiData::buildJs()
     */
    public function buildJs()
    {
        $widget = $this->getWidget();
        
        $configurator_element = $this->getFacade()->getElement($widget->getConfiguratorWidget());
        
        $this->addOnBeforeLoad($this->buildJsOnBeforeLoadAddConfiguratorData());
        
        // Build JS for the editors
        $editorsInit = '';
        if ($this->isEditable()) {
            foreach ($this->getEditors() as $editor) {
                $editorsInit .= $editor->buildJsInlineEditorInit();
            }
        }
        
        // Need to build the autoload disabler BEFORE the grid head is combiled, to make sure
        // the autoload disabler can hook to onXXX events.
        $autoloadInit = $this->buildJsAutoloadDisabler();
        
        $grid_head = '';
        
        // Add row details (expandable rows) if required
        if ($widget->hasRowDetails()) {
            $grid_head .= $this->buildJsInitOptionsRowDetails();
        }
        
        // group rows if required
        if ($widget->hasRowGroups()) {
            $grid_head .= $this->buildJsInitOptionsRowGroups();
        }
        
        // Add editors
        $editorFunctions = '';
        if ($this->isEditable()) {
            $editorFunctions .= $this->buildJsEditableGridFunctions();
        }
        
        // Add scripts for layouting and resizing
        $grid_head .= $this->buildJsInitOptionsLayouter();
        
        // get the standard params for grids and put them before the custom grid head
        $grid_head = $this->buildJsInitOptions() . $grid_head;
        
        return <<<JS

// Add Scripts for the configurator widget first as they may be needed for the others   
{$configurator_element->buildJs()}

$(setTimeout(function(){
    
    {$editorsInit}

    {$autoloadInit}
    
    // Init the table
    $("#{$this->getId()}").{$this->getElementType()}({ {$grid_head} });

    {$this->buildJsPagerButtons()}

    {$this->buildJsContextMenu()}

}, 0));

{$editorFunctions}

{$this->buildJsButtons()}

function {$this->buildJsFunctionPrefix()}_toggleHeader() {
    var confPanel = $('#{$this->getToolbarId()} .exf-data-header');
    var toggleBtn = $('#{$this->getId()}_headerCollapseButton');

    if (confPanel.css('display') === 'none') {
        confPanel.panel('expand');
        toggleBtn.find('.fa-chevron-down').removeClass('fa-chevron-down').addClass('fa-chevron-up');
    } else {
        confPanel.panel('collapse');
        toggleBtn.find('.fa-chevron-up').removeClass('fa-chevron-up').addClass('fa-chevron-down');
    }

    $('#{$this->getId()}').{$this->getElementType()}('resize');
}

JS;
    }

    public function buildJsEditModeEnabler()
    {
        return '
					var rows = $(this).' . $this->getElementType() . '("getRows");
					for (var i=0; i<rows.length; i++){
						$(this).' . $this->getElementType() . '("beginEdit", i);
					}
				';
    }

    /**
     * The getter will return the value of the UID column of the selected row by default.
     * If the parameter row is
     * specified, it will return the UID column of that row. Specifying the column parameter will result in returning
     * the value of that column in the specified row or (if row is not set) the selected row.
     * IDEA perhaps it should return an entire row as an array if the column is not specified. Just have a feeling, it
     * might be better...
     *
     * @see EuiAbstractElement::buildJsValueGetter()
     */
    public function buildJsValueGetter($column = null, $row = null)
    {
        $getSelectedRowsDataJs = "$('#" . $this->getId() . "')";
        
        if (is_null($row)) {
            $getSelectedRowsDataJs .= "." . $this->getElementType() . "('getSelected')";
        } else {
            $getSelectedRowsDataJs .= "." . $this->getElementType() . "('getSelections')[{$row}]";
        }

        if (is_null($column)) {
            if ($this->getWidget()->hasUidColumn() === true) {
                $column = $this->getWidget()->getUidColumn()->getDataColumnName();
            } else {
                throw new FacadeOutputError('Cannot create a value getter for a data widget without a UID column: either specify a column to get the value from or a UID column for the table.');
            }
        } else {
            if (! $col = $this->getWidget()->getColumnByDataColumnName($column)) {
                if ($col = $this->getWidget()->getColumnByAttributeAlias($column)) {
                    $column = $col->getDataColumnName();
                }
            }
        }
        
        // TODO need to list values if multi_select is on instead of just returning the value
        // of the first row (becuase getSelection returns the first row in jEasyUI datagrid)
        return "({$getSelectedRowsDataJs} ? {$getSelectedRowsDataJs}['{$column}'] : '')";
    }

    public function buildJsChangesGetter()
    {
        if ($this->isEditable()) {
            $output = $this->buildJsFunctionPrefix() . "getChanges()";
        } else {
            $output = "[]";
        }
        return $output;
    }

    public function buildJsDataGetter(ActionInterface $action = null)
    {
        $widget = $this->getWidget();
        $rows = '';
        $filters = '';
        
        switch (true) {
            case $action === null:
                $rows = "$('#" . $this->getId() . "')." . $this->getElementType() . "('getData').rows";
                break;
            case $action instanceof iReadData:
                // If we are reading, than we need the special data from the configurator 
                // widget: filters, sorters, etc.
                return $this->getFacade()->getElement($widget->getConfiguratorWidget())->buildJsDataGetter($action);
            case $this->isEditable():
                // Build the row data from the table
                switch (true) {
                    case $action->getMetaObject()->is($widget->getMetaObject()) === true:
                    case $action->getInputMapper($widget->getMetaObject()) !== null:
                        if ($widget->getMultiSelect()) {
                            $rows = "$('#" . $this->getId() . "')." . $this->getElementType() . "('getSelections').length > 0 ? $('#" . $this->getId() . "')." . $this->getElementType() . "('getSelections') : " . $this->buildJsFunctionPrefix() . "getChanges()";
                        } else {
                            $rows = $this->buildJsFunctionPrefix() . "getChanges()";
                        }
                        break 2;
                    default:
                        // If the data is intended for another object, make it a nested data sheet
                        // If the action is based on the same object as the widget's parent, use the widget's
                        // logic to find the relation to the parent. Otherwise try to find a relation to the
                        // action's object and throw an error if this fails.
                        if ($widget->hasParent() && $action->getMetaObject()->is($widget->getParent()->getMetaObject()) && $relPath = $widget->getObjectRelationPathFromParent()) {
                            $relAlias = $relPath->toString();
                        } elseif ($relPath = $action->getMetaObject()->findRelationPath($widget->getMetaObject())) {
                            $relAlias = $relPath->toString();
                        }
                        
                        if ($relAlias === null || $relAlias === '') {
                            throw new WidgetLogicError($widget, 'Cannot use editable table with object "' . $widget->getMetaObject()->getName() . '" (alias ' . $widget->getMetaObject()->getAliasWithNamespace() . ') as input widget for action "' . $action->getName() . '" with object "' . $action->getMetaObject()->getName() . '" (alias ' . $action->getMetaObject()->getAliasWithNamespace() . '): no forward relation could be found from action object to widget object!', '7B7KU9Q');
                        }
                        return <<<JS
        
            {
                oId: '{$action->getMetaObject()->getId()}', 
                rows: [
                    {
                        '{$relAlias}': {
                            oId: '{$widget->getMetaObject()->getId()}', 
                            rows: {$this->buildJsFunctionPrefix()}getDataRows()
                        }
                    }
                ]
            }

JS;
            }
            default:
                $rows = "$('#" . $this->getId() . "')." . $this->getElementType() . "('getSelections')";
        }
        return "{oId: '" . $widget->getMetaObject()->getId() . "'" . ($rows ? ", rows: " . $rows : '') . ($filters ? ", filters: " . $filters : "") . "}";
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsRefresh()
     */
    public function buildJsRefresh($keep_pagination_position = false)
    {
        return '$("#' . $this->getId() . '").' . $this->getElementType() . '("' . ($keep_pagination_position ? 'reload' : 'load') .'")';
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiData::buildHtmlHeadTags()
     */
    public function buildHtmlHeadTags()
    {
        $facade = $this->getFacade();
        $includes = parent::buildHtmlHeadTags();
        // Row details view
        if ($this->getWidget()->hasRowDetails()) {
            $includes[] = '<script type="text/javascript" src="' . $facade->buildUrlToSource('LIBS.JEASYUI.EXTENSIONS.DATAGRID_DETAILVIEW') . '"></script>';
        }
        if ($this->getWidget()->hasRowGroups()){
            $includes[] = '<script type="text/javascript" src="' . $facade->buildUrlToSource('LIBS.JEASYUI.EXTENSIONS.DATAGRID_GROUPVIEW') . '"></script>';
        }
        return $includes;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::getHeight()
     */
    public function getHeight()
    {
        // Die Hoehe der DataTable passt sich nicht automatisch dem Inhalt an. Wenn sie also
        // nicht den gesamten Container ausfuellt, kollabiert sie so dass die Datensaetze nicht
        // mehr sichtbar sind (nur noch Header und Footer). Deshalb wird hier die Hoehe der
        // DataTable gesetzt, wenn sie nicht definiert ist, und sie nicht alleine im Container
        // ist.
        $widget = $this->getWidget();
        
        if ($widget->getHeight()->isUndefined() && ($containerWidget = $widget->getParentByClass('exface\\Core\\Interfaces\\Widgets\\iContainOtherWidgets')) && ($containerWidget->countWidgetsVisible() > 1)) {
            $widget->setHeight($this->getFacade()->getConfig()->getOption('WIDGET.DATATABLE.HEIGHT_DEFAULT'));
        }
        return parent::getHeight();
    }
    
    /* TODO replace getHeight() by this method. It did not work for some reason.
    protected function buildCssHeightDefaultValue()
    {
        $widget = $this->getWidget();
        if ($default_height = $this->getFacade()->getConfig()->getOption('WIDGET.DATATABLE.HEIGHT_DEFAULT')) {
            // Die Hoehe der DataTable passt sich nicht automatisch dem Inhalt an. Wenn sie also
            // nicht den gesamten Container ausfuellt, kollabiert sie so dass die Datensaetze nicht
            // mehr sichtbar sind (nur noch Header und Footer). Deshalb wird hier die Hoehe der
            // DataTable gesetzt, wenn sie nicht definiert ist, und sie nicht alleine im Container
            // ist.
            if ($containerWidget = $widget->getParentByClass('exface\\Core\\Interfaces\\Widgets\\iContainOtherWidgets') && $containerWidget->countWidgetsVisible() > 1) {
                return ($this->getHeightRelativeUnit() * $default_height) . 'px';
            }
        }
        return 'auto';
    }*/
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiData::buildJsInitOptionsColumn()
     */
    protected function buildJsInitOptionsColumn(DataColumn $col){
        $editor = $this->getEditors()[$col->getId()];
        $output = parent::buildJsInitOptionsColumn($col);
        $output .= "\n
                        " . ($editor ? ', editor: {type: "' . $editor->getElementType() . '"' . ($editor->buildJsInitOptions() ? ', options: {' . $editor->buildJsInitOptions() . '}' : '') . '}' : '');
        return $output;
    }
    
    public function buildJsInitOptionsHead()
    {
        $widget = $this->getWidget();
        $this->registerPaginationFixer();
        
        // Add single-result action to onLoadSuccess
        if ($singleResultButton = $widget->getButtons(function($btn) {return ($btn instanceof DataButton) && $btn->isBoundToSingleResult() === true;})[0]) {
            $singleResultJs = <<<JS

                        if (data.rows.length === 1) {
                            var curRow = jqSelf.{$this->getElementType()}("getRows")[0];
                            var lastRow = jqSelf.data("_singleResultActionPerformedFor");
                            if (lastRow === undefined || {$this->buildJsRowCompare('curRow', 'lastRow')} === false){
                                jqSelf.{$this->getElementType()}("selectRow", 0);
                                jqSelf.data("_singleResultActionPerformedFor", curRow);
                                {$this->getFacade()->getElement($singleResultButton)->buildJsClickFunction()};
                            }
                        }

JS;
            $this->addOnLoadSuccess($singleResultJs);
        }
        
        $grid_head = parent::buildJsInitOptionsHead();
        
        // Double click actions. Currently only supports one double click action - the first one in the list of buttons
        if ($dblclick_button = $widget->getButtonsBoundToMouseAction(EXF_MOUSE_ACTION_DOUBLE_CLICK)[0]) {
            $grid_head .= ', onDblClickRow: function(index, row) {' . $this->getFacade()->getElement($dblclick_button)->buildJsClickFunction() . '}';
        }
        
        // Left click actions. Currently only supports one double click action - the first one in the list of buttons
        if ($leftclick_button = $widget->getButtonsBoundToMouseAction(EXF_MOUSE_ACTION_LEFT_CLICK)[0]) {
            $grid_head .= ', onClickRow: function(index, row) {' . $this->getFacade()->getElement($leftclick_button)->buildJsClickFunction() . '}';
        }
        
        // Right click actions or context menu
        if ($rightclick_button = $widget->getButtonsBoundToMouseAction(EXF_MOUSE_ACTION_RIGHT_CLICK)[0]) {
            $grid_head .= ', onClickRow: function(index, row) {' . $this->getFacade()->getElement($rightclick_button)->buildJsClickFunction() . '}';
        } else {
            // Context menu
            if ($widget->getContextMenuEnabled()) {
                $grid_head .= ', onRowContextMenu: function(e, index, row) {
    					e.preventDefault();
    					e.stopPropagation();
                        if (index >= 0){
    					   $(this).' . $this->getElementType() . '("selectRow", index);
                        }
    	                $("#' . $this->getId() . '_cmenu").menu("show", {
    	                    left: e.pageX,
    	                    top: e.pageY
    	                });
    	                return false;
    				}';
            }
        }
        
        $grid_head .= ($this->getOnChangeScript() ? ', onClickRow: function(index, row){' . $this->buildJsOnChangeScript('row', 'index') . '}' : '');
        $grid_head .= ($widget->getCaption() ? ', title: "' . str_replace('"', '\"', $widget->getCaption()) . '"' : '');
        $grid_head .= ', emptyMsg : ' . json_encode($widget->getEmptyText());
        
        return $grid_head;
    }
    
    protected function buildJsInitOptionsRowDetails()
    {
        $widget = $this->getWidget();
        $grid_head = '';
        
        // Create a detail container
        /* @var $details \exface\Core\Widgets\container */
        $details = $widget->getRowDetailsContainer();
        $details_element = $this->getFacade()->getElement($widget->getRowDetailsContainer());
        $details_height = (! $details->getHeight()->isUndefined() ? ", height: '" . $details_element->getHeight() . "'" : "");

        // Add the needed options to our datagrid
        $grid_head .= <<<JS
    				, view: detailview
    				, detailFormatter: function(index,row){
    					return '<div id="{$details_element->getId()}_'+row.{$widget->getMetaObject()->getUidAttributeAlias()}+'"></div>';
    				}
                    , onExpandRow: function(index,row){
    					$('#{$details_element->getId()}_'+row.{$widget->getMetaObject()->getUidAttributeAlias()}).panel({
    		            	border: false,
                            href: "{$this->getAjaxUrl()}",
    						method: 'post',
    						queryParams: {
    							action: '{$widget->getRowDetailsAction()}',
    							resource: '{$widget->getPage()->getAliasWithNamespace()}',
    							element: '{$details->getId()}',
                                exfrid: row.{$widget->getMetaObject()->getUidAttributeAlias()},
    							prefill: {
    								oId: "{$widget->getMetaObject()->getId()}",
    								rows:[
    									{ {$widget->getMetaObject()->getUidAttributeAlias()} : row.{$widget->getMetaObject()->getUidAttributeAlias()} }
    								],
    								filters: {$this->buildJsDataFilters()}
    							}
    						},
    						onLoad: function(){
    		                   	$('#{$this->getId()}').{$this->getElementType()}('fixDetailRowHeight',index);
    		            	},
    		                onLoadError: function(response){
    		                	{$this->buildJsShowErrorAjax('response')}
    						},
    		       			onResize: function(){
    		                	$('#{$this->getId()}').{$this->getElementType()}('fixDetailRowHeight',index);
                    		}
    		         	{$details_height}
    					});
    				}
JS;
        
	    return $grid_head; 
    }
    
    protected function buildJsInitOptionsRowGroups()
    {
        $grid_head = '';
        $grouper = $this->getWidget()->getRowGrouper();
        
        // groupView options
        $prefix = ! $grouper->getHideCaption() ? "'" . $grouper->getCaption() . " ' + " : '';
        $counter = $grouper->getShowCounter() ? " + ' (' + rows.length + ')'" : "";
        $value = "(value || 'empty')";
        if (! $grouper->getHideCaption()) {
            $value = "'\"' + $value + '\"'";
        }
        $grid_head .= ', view: groupview' . ",groupField: '{$grouper->getGroupByColumn()->getDataColumnName()}', groupFormatter:function(value,rows){ return {$prefix}{$value}{$counter};}";
        
        if (! $grouper->getExpandAllGroups()) {
            $this->addOnLoadSuccess("$('#" . $this->getId() . "')." . $this->getElementType() . "('collapseGroup');");
        }
        if ($grouper->getExpandFirstGroupOnly()) {
            $this->addOnLoadSuccess("$('#" . $this->getId() . "')." . $this->getElementType() . "('expandGroup', 0);");
        }
        return $grid_head;
    }
    
    protected function buildJsEditableGridFunctions()
    {
        $output = '';
        $widget = $this->getWidget();
        
        $changes_col_array = array();
        $this->addOnLoadSuccess($this->buildJsEditModeEnabler());
        // add data and changes getter if the grid is editable
        $output .= <<<JS

						function {$this->buildJsFunctionPrefix()}getDataRows(){
							var data = [];
							var jqTable = $('#{$this->getId()}');
							var rows = jqTable.{$this->getElementType()}('getRows');
                            for (var i=0; i<rows.length; i++){
								jqTable.{$this->getElementType()}('endEdit', i);
								data[jqTable.{$this->getElementType()}('getRowIndex', rows[i])] = rows[i];
							}
							return data;
						}
JS;
        
        foreach ($this->getEditors() as $col_id => $editor) {
            $col = $widget->getColumn($col_id);
            
            // Skip editors for columns, that are not attributes
            if (! $col->isBoundToAttribute()) {
                $changes_col_array[] = $col->getDataColumnName();
                continue;
            }
            
            // For all other editors, that belong to related attributes, add some JS to update all rows with that
            // attribute, once the value of one of them changes. This makes sure, that the value of a related attribute
            // is the same, even if it is shown in multiple rows at all times!
            $rel_path = $col->getAttribute()->getRelationPath();
            if ($rel_path && ! $rel_path->isEmpty()) {
                $commonKeyAlias = $rel_path->getRelationLast()->getRightKeyAttribute(true)->getAliasWithRelationPath();
                if ($commonKeyCol = $widget->getColumnByAttributeAlias($commonKeyAlias)) {
                    $commonKeyColName = $commonKeyCol->getDataColumnName();
                    $this->addOnLoadSuccess("$('td[field=\'" . $col->getDataColumnName() . "\'] input').change(function(){
    					var rows = $('#" . $this->getId() . "')." . $this->getElementType() . "('getRows');
                        var thisRowIdx = $(this).parents('tr.datagrid-row').attr('datagrid-row-index');
    					var thisRowUID = rows[thisRowIdx]['" . $commonKeyColName . "'];
                        var val = $(this).{$editor->buildJsValueGetterMethod()};
    					for (var i=0; i<rows.length; i++) {
    						if (rows[i]['" . $commonKeyColName . "'] == thisRowUID){
    							var ed = $('#" . $this->getId() . "')." . $this->getElementType() . "('getEditor', {index: i, field: '" . $col->getDataColumnName() . "'});
    							var ed$ = $(ed.target);
                                if (val != ed$.{$editor->buildJsValueGetterMethod()}) {
                                    ed$." . $editor->buildJsValueSetterMethod("val") . ";
                                }
    						}
    					}
    				});");
                }
            }
            
            $changes_col_array[] = $col->getDataColumnName();
        }
        
        $changes_cols = implode("','", $changes_col_array);
        
        if ($changes_cols){
            $changes_cols = "'" . $changes_cols . "'";
        }
            
        foreach ($widget->getColumnsWithSystemAttributes() as $col) {
            $changes_cols .= ",'" . $col->getDataColumnName() . "'";
        }
        $changes_cols = trim($changes_cols, ',');
        
        $output .= <<<JS

					function {$this->buildJsFunctionPrefix()}getChanges(){
						var data = [];
						var cols = [{$changes_cols}];
						var rowCount = $('#{$this->getId()}').{$this->getElementType()}('getRows').length;
						for (var i=0; i<rowCount; i++){
							$('#{$this->getId()}').{$this->getElementType()}('endEdit', i);
						}
						rows = $('#{$this->getId()}').{$this->getElementType()}('getChanges');
						for (var i=0; i<rows.length; i++){
							var row = {};
							for (var j=0; j<cols.length; j++){
								row[cols[j]] = rows[i][cols[j]];
							}
							data.push(row);
						}
						return data;
					}

JS;
        
        return $output;
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsInitOptionsLayouter()
    {
        $grid_head = '';
        
        // Auf manchen Seiten (z.B. Kundenreklamation) kam es nach dem Laden zu Fehlern im Layout
        // (Tabelle nimmt nicht den gesamten verfügbaren Raum ein -> weißer Rand darunter, Spalten-
        // Header sind schmaler als die Inhalte -> verschoben). Durch den Aufruf von "autoSizeColumn"
        // onResize wird das Layout nach dem Laden oder ausklappen der SideBar erneuert. (Auch
        // möglich wäre ein Aufruf von "resize" (dann werden aber die Spaltenbreiten nicht
        // korrigiert) oder "autoSizeColumn" onLoadSuccess ($this->addOnLoadSuccess()) und
        // onLoadError u.U. mit setTimeout()). Durch diese Aenderung wird das Layout leider etwas
        // traeger.
        $resize_function = $this->getOnResizeScript();
        $resize_function .= '
					$("#' . $this->getId() . '").' . $this->getElementType() . '("autoSizeColumn");';
        $grid_head .= ', fit: true
				, onResize: function(){' . $resize_function . '}';
        return $grid_head;
    }
    
    protected function buildJsPagerButtons()
    {
        $widget = $this->getWidget();
        $output = '';
        
        // Add buttons to the pager at the bottom of the datagrid
        $bottom_buttons = array();
        $bottom_buttons[] = <<<JS
                    
                    {
						iconCls:  "fa fa-square-o",
						title: "{$this->translate('WIDGET.DATATABLE.CLEAR_SELECTIONS')}",
						handler: function() { 
                            {$this->buildJsValueResetter()}
                        }
					}
JS;
        
        // If the top toolbar is hidden, add actions to the bottom toolbar
        if ($widget->getHideHeader() && ! $widget->getHideFooter() && $widget->hasButtons()) {
            foreach ($widget->getToolbars() as $toolbar) {
                foreach ($toolbar->getButtonGroups() as $button_group){
                    if ($toolbar->getIncludeSearchActions() && $button_group === $toolbar->getButtonGroupForSearchActions()){
                        continue;
                    }
                    if (! empty($bottom_buttons) && ! $button_group->isEmpty()){
                        $bottom_buttons[] = '"-"';
                    }
                    foreach ($button_group->getButtons() as $button){
                        if ($button->isHidden() || $button instanceof MenuButton){
                            continue;
                        }
                        
                        $bottom_buttons[] = '{
        					iconCls:  "' . $this->buildCssIconClass($button->getIcon()) . '",
        					title: "' . str_replace('"', '\"', $button->getCaption()) . '",
        					handler: ' . $this->getFacade()->getElement($button)->buildJsClickFunctionName() . '
        				}';
                    }
                }                
            }
        }
        
        // Add the help button in the bottom toolbar
        if (! $widget->getHideHelpButton()) {
            $output .= $this->getFacade()->buildJs($widget->getHelpButton());
            $bottom_buttons[] = '{
						iconCls:  "fa fa-question-circle-o",
						title: "' . $this->translate('HELP') . '",
						handler: ' . $this->getFacade()->getElement($widget->getHelpButton())->buildJsClickFunctionName() . '
					}';
        }
        
        if (! empty($bottom_buttons)) {
            $output .= '
                
							var pager = $("#' . $this->getId() . '").' . $this->getElementType() . '("getPager");
	            			pager.pagination({
								buttons: [' . implode(', ', $bottom_buttons) . ']
							});
								    
					';
        }
        
        return $output;
    }
    
    protected function registerPaginationFixer() : string
    {
        $modifyLoadedData = <<<JS

                    if (data.total === null) {
                        data.total = data.rows.length + data.offset + 1;
                        $(this).data("_totalRowCounterPlaceholder", data.total);
                    } else {
                        $(this).removeData("_totalRowCounterPlaceholder");
                    }

JS;
        
        $updatePager = <<<JS

                    if ((jqSelf.data("_totalRowCounterPlaceholder") !== undefined) && jqSelf.prop('nodeName') === 'TABLE') {
                        var pInfo = jqSelf.datagrid("getPager").find('.pagination-info');
                        pInfo.text(pInfo.text().replace(jqSelf.data("_totalRowCounterPlaceholder"), '?'));
                    }                         

JS;
        $this->addLoadFilterScript($modifyLoadedData);
        $this->addOnLoadSuccess($updatePager);
        return '';
    }
    
    /**
     * Returns a JS snippet, that empties the table (removes all rows).
     * 
     * @return string
     */
    protected function buildJsDataResetter() : string
    {
        return "$('#{$this->getId()}').{$this->getElementType()}('loadData', {rows: []});";
    }
    
    protected function buildJsOnBeforeLoadAddConfiguratorData(string $js_var_param = 'param') : string
    {
        return parent::buildJsOnBeforeLoadAddConfiguratorData($js_var_param) . <<<JS

                    // Enrich sorting options
                    if ({$js_var_param}.sort !== undefined) {
                        var sortNames = {$js_var_param}.sort.split(',');
                        var sortAttrs = [];
                        for (var i=0; i<sortNames.length; i++) {
                            colOpts = jqself.{$this->getElementType()}('getColumnOption', sortNames[i]);
                            sortAttrs.push(colOpts !== null ? colOpts['_attributeAlias'] : sortNames[i]);
                        }
                        {$js_var_param}.sortAttr = sortAttrs.join(',');
                    }

JS;
    }
}