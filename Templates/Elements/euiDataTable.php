<?php
namespace exface\JEasyUiTemplate\Templates\Elements;

use exface\Core\Widgets\DataTable;
use exface\Core\Interfaces\Actions\ActionInterface;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryDataTableTrait;
use exface\Core\Interfaces\Actions\iReadData;
use exface\Core\Widgets\DataColumn;
use exface\Core\Widgets\MenuButton;

/**
 *
 * @author Andrej Kabachnik
 *        
 * @method DataTable getWidget()
 *        
 */
class euiDataTable extends euiData
{
    
    use JqueryDataTableTrait;

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiData::init()
     */
    protected function init()
    {
        parent::init();
        $this->setElementType('datagrid');
        $widget = $this->getWidget();
        
        // Take care of refresh links
        if ($refresh_link = $widget->getRefreshWithWidget()) {
            if ($refresh_link_element = $this->getTemplate()->getElement($refresh_link->getWidget())) {
                $refresh_link_element->addOnChangeScript($this->buildJsRefresh());
            }
        }
        
        // Initialize editors
        /* @var $col \exface\Core\Widgets\DataColumn */
        foreach ($widget->getColumns() as $col) {
            if ($col->isEditable()) {
                $editor = $this->getTemplate()->getElement($col->getCellWidget());
                $this->setEditable(true);
                $this->editors[$col->getId()] = $editor;
            }
        }
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiData::buildHtml()
     */
    public function buildHtml()
    {
        $widget = $this->getWidget();
        
        if ($widget->getHideHeader()){
            $header_style = 'visibility: hidden; height: 0px; padding: 0px;';
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
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiData::buildJs()
     */
    public function buildJs()
    {
        $widget = $this->getWidget();
        $output = '';
        
        // Add Scripts for the configurator widget first as they may be needed for the others
        $configurator_element = $this->getTemplate()->getElement($widget->getConfiguratorWidget());
        $output .= $configurator_element->buildJs();
        $on_before_load = <<<JS
            
                try {
                    if (! {$configurator_element->buildJsValidator()}) {
                        return false;
                    } 
                } catch (e) {
                    console.warn('Could not check filter validity - ', e);
                }
                param['data'] = {$configurator_element->buildJsDataGetter()};
        
JS;
        $this->addOnBeforeLoad($on_before_load);
        // Add a script to remove selected but not present rows onLoadSuccess. getRowIndex returns
        // -1 for selected but not present rows. Selections outlive a reload but the selected row
        // may have been deleted in the meanwhile. An example is "offene Positionen stornieren" in
        // "Rueckstandsliste".
        $onLoadSuccessScript = <<<JS

				var jqself = $(this);
                var rows = jqself.{$this->getElementType()}("getSelections");
                var selectedRows = [];
                for (var i = 0; i < rows.length; i++) {
                    var index = jqself.{$this->getElementType()}("getRowIndex", rows[i]);
                    if( index >= 0) {
                        selectedRows.push(index);
                    }
                }
                jqself.{$this->getElementType()}("clearSelections");
                for (var i = 0; i < selectedRows.length; i++) {
                    jqself.{$this->getElementType()}("selectRow", selectedRows[i]);
                }
JS;
        $this->addOnLoadSuccess($onLoadSuccessScript);
        
        // Build JS for the editors
        if ($this->isEditable()) {
            foreach ($this->getEditors() as $editor) {
                $output .= $editor->buildJsInlineEditorInit();
            }
        }
        
        // Wenn noetig initiales Laden ueberspringen.
        if (! $widget->getAutoloadData() && $widget->getLazyLoading()) {
            $output .= <<<JS

            $("#{$this->getId()}").data("_skipNextLoad", true);
JS;
            
            // Dieses Skript wird nach dem erfolgreichen Laden ausgefuehrt, um die angezeigte
            // Nachricht (s.u.) zu entfernen. Das Skript muss vor $grid_head erzeugt werden.
            $this->addOnLoadSuccess($this->buildJsNoInitialLoadMessageRemove());
        }
        
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
        if ($this->isEditable()) {
            $output .= $this->buildJsEditableGridFunctions();
        }
        
        // Add scripts for layouting and resizing
        $grid_head .= $this->buildJsInitOptionsLayouter();
        
        // get the standard params for grids and put them before the custom grid head
        $grid_head = $this->buildJsInitOptions() . $grid_head;
        
        // instantiate the data grid
        $output .= '
            $("#' . $this->getId() . '").' . $this->getElementType() . '({' . $grid_head . '});
        ';
        
        // Eine Nachricht anzeigen, dass keine Daten geladen wurde, wenn das initiale Laden
        // uebersprungen wird.
        if (! $widget->getAutoloadData() && $widget->getLazyLoading()) {
            $output .= $this->buildJsNoInitialLoadMessageShow();
        }
        
        // build JS for the button actions
        $output .= $this->buildJsButtons();
        
        $output .= $this->buildJsPagerButtons();
        
        $output .= $this->buildJsContextMenu();
        
        return $output;
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
     * @see euiAbstractElement::buildJsValueGetter()
     */
    public function buildJsValueGetter($column = null, $row = null)
    {
        $output = "$('#" . $this->getId() . "')";
        if (is_null($row)) {
            $output .= "." . $this->getElementType() . "('getSelected')";
        }
        if (is_null($column)) {
            $column = $this->getWidget()->getMetaObject()->getUidAttributeAlias();
        }
        return "(" . $output . " ? " . $output . "['" . $column . "'] : '')";
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
        $rows = '';
        $filters = '';
        if (is_null($action)) {
            $rows = "$('#" . $this->getId() . "')." . $this->getElementType() . "('getData')";
        } elseif ($action instanceof iReadData) {
            // If we are reading, than we need the special data from the configurator 
            // widget: filters, sorters, etc.
            return $this->getTemplate()->getElement($this->getWidget()->getConfiguratorWidget())->buildJsDataGetter($action);
        } elseif ($this->isEditable() && $action->implementsInterface('iModifyData')) {
            if ($this->getWidget()->getMultiSelect()) {
                $rows = "$('#" . $this->getId() . "')." . $this->getElementType() . "('getSelections').length > 0 ? $('#" . $this->getId() . "')." . $this->getElementType() . "('getSelections') : " . $this->buildJsFunctionPrefix() . "getChanges()";
            } else {
                $rows = $this->buildJsFunctionPrefix() . "getChanges()";
            }
        } else {
            $rows = "$('#" . $this->getId() . "')." . $this->getElementType() . "('getSelections')";
        }
        return "{oId: '" . $this->getWidget()->getMetaObject()->getId() . "'" . ($rows ? ", rows: " . $rows : '') . ($filters ? ", filters: " . $filters : "") . "}";
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJsRefresh()
     */
    public function buildJsRefresh($keep_pagination_position = false)
    {
        return '$("#' . $this->getId() . '").' . $this->getElementType() . '("' . ($keep_pagination_position ? 'reload' : 'load') .'")';
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiData::buildHtmlHeadTags()
     */
    public function buildHtmlHeadTags()
    {
        $includes = parent::buildHtmlHeadTags();
        // Masonry is neede to align filters nicely
        $includes[] = '<script type="text/javascript" src="exface/vendor/bower-asset/masonry/dist/masonry.pkgd.min.js"></script>';
        // Row details view
        if ($this->getWidget()->hasRowDetails()) {
            $includes[] = '<script type="text/javascript" src="exface/vendor/exface/JEasyUiTemplate/Templates/js/jeasyui/extensions/datagridview/datagrid-detailview.js"></script>';
        }
        if ($this->getWidget()->hasRowGroups()){
            $includes[] = '<script type="text/javascript" src="exface/vendor/exface/JEasyUiTemplate/Templates/js/jeasyui/extensions/datagridview/datagrid-groupview.js"></script>';
        }
        return $includes;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiAbstractElement::getHeight()
     */
    public function getHeight()
    {
        // Die Hoehe der DataTable passt sich nicht automatisch dem Inhalt an. Wenn sie also
        // nicht den gesamten Container ausfuellt, kollabiert sie so dass die Datensaetze nicht
        // mehr sichtbar sind (nur noch Header und Footer). Deshalb wird hier die Hoehe der
        // DataTable gesetzt, wenn sie nicht definiert ist, und sie nicht alleine im Container
        // ist.
        $widget = $this->getWidget();
        
        if ($widget->getHeight()->isUndefined() && ($containerWidget = $widget->getParentByType('exface\\Core\\Interfaces\\Widgets\\iContainOtherWidgets')) && ($containerWidget->countWidgetsVisible() > 1)) {
            $widget->setHeight($this->getTemplate()->getConfig()->getOption('WIDGET.DATATABLE.HEIGHT_DEFAULT'));
        }
        return parent::getHeight();
    }
    
    /* TODO replace getHeight() by this method. It did not work for some reason.
    protected function buildCssHeightDefaultValue()
    {
        $widget = $this->getWidget();
        if ($default_height = $this->getTemplate()->getConfig()->getOption('WIDGET.DATATABLE.HEIGHT_DEFAULT')) {
            // Die Hoehe der DataTable passt sich nicht automatisch dem Inhalt an. Wenn sie also
            // nicht den gesamten Container ausfuellt, kollabiert sie so dass die Datensaetze nicht
            // mehr sichtbar sind (nur noch Header und Footer). Deshalb wird hier die Hoehe der
            // DataTable gesetzt, wenn sie nicht definiert ist, und sie nicht alleine im Container
            // ist.
            if ($containerWidget = $widget->getParentByType('exface\\Core\\Interfaces\\Widgets\\iContainOtherWidgets') && $containerWidget->countWidgetsVisible() > 1) {
                return ($this->getHeightRelativeUnit() * $default_height) . 'px';
            }
        }
        return 'auto';
    }*/
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiData::buildJsInitOptionsColumn()
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
        $grid_head = parent::buildJsInitOptionsHead();
        
        // Double click actions. Currently only supports one double click action - the first one in the list of buttons
        if ($dblclick_button = $widget->getButtonsBoundToMouseAction(EXF_MOUSE_ACTION_DOUBLE_CLICK)[0]) {
            $grid_head .= ', onDblClickRow: function(index, row) {' . $this->getTemplate()->getElement($dblclick_button)->buildJsClickFunction() . '}';
        }
        
        // Left click actions. Currently only supports one double click action - the first one in the list of buttons
        if ($leftclick_button = $widget->getButtonsBoundToMouseAction(EXF_MOUSE_ACTION_LEFT_CLICK)[0]) {
            $grid_head .= ', onClickRow: function(index, row) {' . $this->getTemplate()->getElement($leftclick_button)->buildJsClickFunction() . '}';
        }
        
        // Right click actions or context menu
        if ($rightclick_button = $widget->getButtonsBoundToMouseAction(EXF_MOUSE_ACTION_RIGHT_CLICK)[0]) {
            $grid_head .= ', onClickRow: function(index, row) {' . $this->getTemplate()->getElement($rightclick_button)->buildJsClickFunction() . '}';
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
        
        $grid_head .= ($this->getOnChangeScript() ? ', onSelect: function(index, row){' . $this->getOnChangeScript() . '}' : '');
        $grid_head .= ($widget->getCaption() ? ', title: "' . str_replace('"', '\"', $widget->getCaption()) . '"' : '');
        
        return $grid_head;
    }
    
    protected function buildJsInitOptionsRowDetails()
    {
        $widget = $this->getWidget();
        $grid_head = '';
        
        // Create a detail container
        /* @var $details \exface\Core\Widgets\container */
        $details = $widget->getRowDetailsContainer();
        $details_element = $this->getTemplate()->getElement($widget->getRowDetailsContainer());
        $details_height = (! $details->getHeight()->isUndefined() ? ", height: '" . $details_element->getHeight() . "'" : "");

        $headers = ! empty($this->getAjaxHeaders()) ? 'headers: ' . json_encode($this->getAjaxHeaders()) . ',' : '';
        
        // Add the needed options to our datagrid
        $grid_head .= <<<JS
    				, view: detailview
    				, detailFormatter: function(index,row){
    					return '<div id="{$details_element->getId()}_'+row.{$widget->getMetaObject()->getUidAttributeAlias()}+'"></div>';
    				}
    				, onExpandRow: function(index,row){
                        var headers = {$headers};
                        headers['Subrequest-ID'] = row.{$widget->getMetaObject()->getUidAttributeAlias()};
    					$('#{$details_element->getId()}_'+row.{$widget->getMetaObject()->getUidAttributeAlias()}).panel({
    		            	border: false,
    						headers: headers,
    		            	method: 'post',
    						queryParams: {
    							action: '{$widget->getRowDetailsAction()}',
    							resource: '{$widget->getPage()->getAliasWithNamespace()}',
    							element: '{$details->getId()}',
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
    		                	{$this->buildJsShowError('response.responseText', 'response.status + " " + response.statusText')}
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
        
        $grid_head .= ', view: groupview' . ",groupField: '" . $grouper->getGroupByColumn()->getDataColumnName() . "'" . ",groupFormatter:function(value,rows){ return value" . ($grouper->getShowCounter() ? " + ' (' + rows.length + ')'" : "") . ";}";
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
        $output .= "
						function " . $this->buildJsFunctionPrefix() . "getData(){
							var data = [];
							var rows = $('#" . $this->getId() . "')." . $this->getElementType() . "('getRows');
							for (var i=0; i<rows.length; i++){
								$('#" . $this->getId() . "')." . $this->getElementType() . "('endEdit', i);
								data[$('#" . $this->getId() . "')." . $this->getElementType() . "('getRowIndex', rows[i])] = rows[i];
							}
							return data;
						}";
        foreach ($this->getEditors() as $col_id => $editor) {
            $col = $widget->getColumn($col_id);
            // Skip editors for columns, that are not attributes
            if (! $col->hasAttributeReference())
                continue;
                // For all other editors, that belong to related attributes, add some JS to update all rows with that
                // attribute, once the value of one of them changes. This makes sure, that the value of a related attribute
                // is the same, even if it is shown in multiple rows at all times!
                $rel_path = $col->getAttribute()->getRelationPath();
                if ($rel_path && ! $rel_path->isEmpty()) {
                    $col_obj_uid = $rel_path->getRelationLast()->getRelatedObjectKeyAttribute()->getAliasWithRelationPath();
                    $this->addOnLoadSuccess("$('td[field=\'" . $col->getDataColumnName() . "\'] input').change(function(){
						var rows = $('#" . $this->getId() . "')." . $this->getElementType() . "('getRows');
						var thisRowIdx = $(this).parents('tr.datagrid-row').attr('datagrid-row-index');
						var thisRowUID = rows[thisRowIdx]['" . $col_obj_uid . "'];
						for (var i=0; i<rows.length; i++){
							if (rows[i]['" . $col_obj_uid . "'] == thisRowUID){
								var ed = $('#" . $this->getId() . "')." . $this->getElementType() . "('getEditor', {index: i, field: '" . $col->getDataColumnName() . "'});
								$(ed.target)." . $editor->buildJsValueSetterMethod("$(this)." . $editor->buildJsValueGetterMethod()) . ";
							}
						}
					});");
                }
                
                $changes_col_array[] = $widget->getColumn($col_id)->getDataColumnName();
        }
        
        $changes_cols = implode("','", $changes_col_array);
        
        if ($changes_cols){
            $changes_cols = "'" . $changes_cols . "'";
        }
            
        foreach ($widget->getColumnsWithSystemAttributes() as $col) {
            $changes_cols .= ",'" . $col->getDataColumnName() . "'";
        }
        $changes_cols = trim($changes_cols, ',');
        
        $output .= "
					function " . $this->buildJsFunctionPrefix() . "getChanges(){
						var data = [];
						var cols = [" . $changes_cols . "];
						var rowCount = $('#" . $this->getId() . "')." . $this->getElementType() . "('getRows').length;
						for (var i=0; i<rowCount; i++){
							$('#" . $this->getId() . "')." . $this->getElementType() . "('endEdit', i);
						}
						rows = $('#" . $this->getId() . "')." . $this->getElementType() . "('getChanges');
						for (var i=0; i<rows.length; i++){
							$('#" . $this->getId() . "')." . $this->getElementType() . "('endEdit', i);
							var row = {};
							for (var j=0; j<cols.length; j++){
								row[cols[j]] = rows[i][cols[j]];
							}
							data.push(row);
						}
						return data;
					}";
        
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
        					handler: ' . $this->getTemplate()->getElement($button)->buildJsClickFunctionName() . '
        				}';
                    }
                }                
            }
        }
        
        // Add the help button in the bottom toolbar
        if (! $widget->getHideHelpButton()) {
            $output .= $this->getTemplate()->buildJs($widget->getHelpButton());
            $bottom_buttons[] = '{
						iconCls:  "fa fa-question-circle-o",
						title: "' . $this->translate('HELP') . '",
						handler: ' . $this->getTemplate()->getElement($widget->getHelpButton())->buildJsClickFunctionName() . '
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

    /**
     * Generates JS code to show a message if the initial load was skipped.
     * 
     * @return string
     */
    protected function buildJsNoInitialLoadMessageShow()
    {
        $widget = $this->getWidget();
        
        $output = <<<JS

            $("#{$this->getId()}").parent().append("\
                <div id='{$this->getId()}_no_initial_load_message'\
                     class='no-initial-load-message-overlay'>\
                    <table class='no-initial-load-message-overlay-table'>\
                        <tr>\
                            <td style='text-align:center;'>\
                                {$widget->getTextNotLoaded()}\
                            </td>\
                        </tr>\
                    </table>\
                </div>\
            ");
JS;
        
        return $output;
    }

    /**
     * Generates JS code to remove the message if the initial load was skipped.
     * 
     * @return string
     */
    protected function buildJsNoInitialLoadMessageRemove()
    {
        $output = <<<JS

        $("#{$this->getId()}_no_initial_load_message").remove();
JS;
        
        return $output;
    }
}
?>