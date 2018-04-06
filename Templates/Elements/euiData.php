<?php
namespace exface\JEasyUiTemplate\Templates\Elements;

use exface\Core\Widgets\DataColumnGroup;
use exface\Core\Widgets\Data;
use exface\Core\Exceptions\Configuration\ConfigOptionNotFoundError;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryToolbarsTrait;
use exface\Core\Widgets\MenuButton;
use exface\Core\Widgets\Button;
use exface\Core\Widgets\Tabs;
use exface\Core\Interfaces\Widgets\iHaveContextMenu;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryAlignmentTrait;
use exface\Core\Widgets\ButtonGroup;
use exface\Core\DataTypes\SortingDirectionsDataType;
use exface\Core\Interfaces\DataSheets\DataSheetInterface;
use exface\Core\Factories\DataColumnFactory;
use exface\Core\Widgets\DataColumn;
use exface\Core\Interfaces\DataTypes\EnumDataTypeInterface;
use exface\Core\DataTypes\NumberDataType;
use exface\Core\DataTypes\TextStylesDataType;
use exface\Core\DataTypes\DateDataType;
use exface\Core\DataTypes\TimestampDataType;
use exface\Core\Templates\AbstractAjaxTemplate\Interfaces\JsValueDecoratingInterface;
use exface\Core\Interfaces\Widgets\iShowText;
use exface\Core\Interfaces\Widgets\iDisplayValue;

/**
 * Implementation of a basic grid.
 *
 * @method Data getWidget()
 *
 * @author Andrej Kabachnik
 *
 */
class euiData extends euiAbstractElement
{
    use JqueryToolbarsTrait;
    
    use JqueryAlignmentTrait;
    
    private $toolbar_id = null;
    
    private $show_footer = null;
    
    private $on_before_load = '';
    
    private $on_load_success = '';
    
    private $on_load_error = '';
    
    private $load_filter_script = '';
    
    private $headers_colspan = array();
    
    private $headers_rowspan = array();
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::init()
     */
    protected function init()
    {
        parent::init();
        $widget = $this->getWidget();
        
        // Prepare the configurator widget
        $widget->getConfiguratorWidget()
        ->setTabPosition(Tabs::TAB_POSITION_RIGHT)
        ->setHideTabsCaptions(true);
    }
    
    /**
     * The Data element by itself does not generate anything - it just offers common utility methods.
     *
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildHtml()
     */
    public function buildHtml()
    {
        return '';
    }
    
    /**
     * The Data element by itself does not generate anything - it just offers common utility methods.
     *
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::generateJ()
     */
    public function buildJs()
    {
        return '';
    }
    
    /**
     *
     * @return boolean
     */
    protected function isLazyLoading()
    {
        return $this->getWidget()->getLazyLoading(true);
    }
    
    /**
     * Generates config-elements for the js grid instatiator, that define the data source for the grid.
     * By default the data source is remote and will be fetched via AJAX. Override this method for local data sources.
     *
     * @return string
     */
    public function buildJsDataSource()
    {
        $widget = $this->getWidget();
        
        if ($this->isLazyLoading()) {
            // Lazy loading via AJAX
            $params = array();
            $queryParams = array(
                'resource' => $widget->getPage()->getAliasWithNamespace(),
                'element' => $widget->getId(),
                'object' => $this->getWidget()->getMetaObject()->getId(),
                'action' => $widget->getLazyLoadingActionAlias()
            );
            foreach ($queryParams as $param => $val) {
                $params[] = $param . ': "' . $val . '"';
            }
            
            $result = '
				url: "' . $this->getAjaxUrl() . '"
				, queryParams: {' . implode("\n\t\t\t\t\t, ", $params) . '}';
        } else {
            // Data embedded in the code of the DataGrid
            $data = $widget->prepareDataSheetToRead($widget->getValuesDataSheet());
            if (! $data->isFresh()) {
                $data->dataRead();
            }
            $result = '
				remoteSort: false
				, loader: function(param, success, error) {' . $this->buildJsDataLoaderWithoutAjax($data) . '}';
        }
        
        return $result;
    }
    
    public function buildJsInitOptionsHead()
    {
        $widget = $this->getWidget();
        
        // add initial sorters
        $sort_by = [];
        $direction = [];
        if ($this->isLazyLoading() && count($widget->getSorters()) > 0) {
            foreach ($widget->getSorters() as $sort) {
                // Check if sorting over a column and use the column name in this case
                // to ensure, the sorting indicator lights up in the column header.
                if ($col = $widget->getColumnByAttributeAlias($sort->getProperty('attribute_alias'))){
                    $sort_by[] = urlencode($col->getDataColumnName());
                } else {
                    $sort_by[] = urlencode($sort->getProperty('attribute_alias'));
                }
                $direction[] = strcasecmp($sort->getProperty('direction'), SortingDirectionsDataType::ASC) === 0 ? 'asc' : 'desc';
            }
            $sortColumn = ", sortName: '" . implode(',', $sort_by) . "'";
            $sortOrder = ", sortOrder: '" . implode(',', $direction) . "'";
        }
        
        if (! is_null($widget->getPaginatePageSize())) {
            $default_page_size = $widget->getPaginatePageSize();
        } else {
            try {
                $default_page_size = $this->getTemplate()->getConfig()->getOption('WIDGET.' . $widget->getWidgetType() . '.PAGE_SIZE');
            } catch (ConfigOptionNotFoundError $e) {
                $default_page_size = $this->getTemplate()->getConfig()->getOption('WIDGET.DATATABLE.PAGE_SIZE');
            }
        }
        
        $page_sizes = $this->getTemplate()->getApp()->getConfig()->getOption('WIDGET.DATATABLE.PAGE_SIZES_SELECTABLE')->toArray();
        if (!in_array($default_page_size, $page_sizes)){
            $page_sizes[] = $default_page_size;
            sort($page_sizes);
        }
        
        // Make sure, all selections are cleared, when the data is loaded from the backend. This ensures, the selected rows are always visible to the user!
        if ($widget->getMultiSelect()) {
            // TODO: Gibt Probleme im Context einer ComboTable. Dort muesste die Zeile folgendermassen
            // aussehen: $(this).combogrid("grid").' . $this->getElementType() . '("clearSelections");
            // Ist es fuer eine ComboTable sinnvoll nach jedem Laden ihre Auswahl zu verlieren???
            // $this->addOnLoadSuccess('$(this).' . $this->getElementType() . '("clearSelections");');
            
            // Autoselect all rows if neccessary
            if ($widget->getMultiSelectAllSelected()){
                $this->addOnLoadSuccess("$('#" . $this->getId() . "')." . $this->getElementType() . "('selectAll');");
            }
        }
        
        $output = '
				, rownumbers: ' . ($widget->getShowRowNumbers() ? 'true' : 'false') . '
				, fitColumns: true
				, multiSort: ' . ($widget->getHeaderSortMultiple() ? 'true' : 'false') . '
				' . $sortColumn . $sortOrder . '
				' . ($widget->getUidColumnId() ? ', idField: "' . $widget->getUidColumn()->getDataColumnName() . '"' : '') . '
				' . (! $widget->getMultiSelect() ? ', singleSelect: true' : '') . '
				' . ($this->getWidth() ? ', width: "' . $this->getWidth() . '"' : '') . '
				, pagination: ' . ($widget->getPaginate() ? 'true' : 'false') . '
				' . ($widget->getPaginate() ? ', pageList: ' . json_encode($page_sizes) : '') . '
				, showFooter: ' . ($widget->hasColumnFooters() ? 'true' : 'false') . '
                , pageSize: ' . $default_page_size . '
				, striped: ' . ($widget->getStriped() ? 'true' : 'false') . '
				, nowrap: ' . ($widget->getNowrap() ? 'true' : 'false') . '
				, toolbar: "#' . $this->getToolbarId() . '"
				' . ($this->buildJsOnBeforeLoadFunction() ? ', onBeforeLoad: ' . $this->buildJsOnBeforeLoadFunction() : '') . '
				' . ($this->getOnLoadSuccess() ? ', onLoadSuccess: function(data) {
					' . $this->getOnLoadSuccess() . '
				}' : '') . '
				, onLoadError: function(response) {
					' . $this->buildJsShowError('response.responseText', 'response.status + " " + response.statusText') . '
					' . $this->getOnLoadError() . '
				}
				' . ($this->getLoadFilterScript() ? ', loadFilter: function(data) {
					' . $this->getLoadFilterScript() . '
					return data;
				}' : '') . '
				, columns: [ ' . implode(',', $this->buildJsInitOptionsColumns()) . ' ]';
        return $output;
    }
    
    public function buildJsInitOptionsColumns(array $column_groups = null)
    {
        if (! $column_groups) {
            $column_groups = $this->getWidget()->getColumnGroups();
        }
        
        // render the columns
        $header_rows = array();
        $full_height_column_groups = array();
        if ($this->getWidget()->getMultiSelect()) {
            $header_rows[0][0] = '{field: "ck", checkbox: true}';
        }
        /* @var $column_group \exface\Core\Widgets\DataColumnGroup */
        // Set the rowspan for column groups with a caption and remember those without a caption to set the colspan later
        foreach ($column_groups as $column_group) {
            if (! $column_group->getCaption()) {
                $full_height_column_groups[] = $column_group;
            }
        }
        // Now set colspan = 2 for all full height columns, if there are two rows of columns
        if (count($full_height_column_groups) != count($column_groups)) {
            foreach ($full_height_column_groups as $column_group) {
                $this->setColumnHeaderRowspan($column_group, 2);
            }
            if ($this->getWidget()->getMultiSelect()) {
                $header_rows[0][0] = '{field: "ck", checkbox: true, rowspan: 2}';
            }
        }
        // Now loop through all column groups again and built the header definition
        foreach ($column_groups as $column_group) {
            if ($column_group->getCaption()) {
                $header_rows[0][] = '{title: "' . str_replace('"', '\"', $column_group->getCaption()) . '", colspan: ' . $column_group->countColumnsVisible() . '}';
                $put_into_header_row = 1;
            } else {
                $put_into_header_row = 0;
            }
            foreach ($column_group->getColumns() as $col) {
                $header_rows[$put_into_header_row][] = '{' . $this->buildJsInitOptionsColumn($col) . '}';
            }
        }
        
        foreach ($header_rows as $i => $row) {
            $header_rows[$i] = '[' . implode(',', $row) . ']';
        }
        
        return $header_rows;
    }
    
    protected function setColumnHeaderColspan(DataColumnGroup $column_group, $colspan)
    {
        foreach ($column_group->getColumns() as $col) {
            $this->headers_colspan[$col->getId()] = $colspan;
        }
        return $this;
    }
    
    protected function getColumnHeaderColspan($column_id)
    {
        return $this->headers_colspan[$column_id];
    }
    
    protected function setColumnHeaderRowspan(DataColumnGroup $column_group, $rowspan)
    {
        foreach ($column_group->getColumns() as $col) {
            $this->headers_rowspan[$col->getId()] = $rowspan;
        }
        return $this;
    }
    
    protected function getColumnHeaderRowspan($column_id)
    {
        return $this->headers_rowspan[$column_id];
    }
    
    protected function buildJsInitOptionsColumn(\exface\Core\Widgets\DataColumn $col)
    {
        $colspan = $this->getColumnHeaderColspan($col->getId());
        $rowspan = $this->getColumnHeaderRowspan($col->getId());
        
        // In datagrids with remote source sorting is allways performed remotely, so
        // it cannot be done for columns without attribute binding (the server cannot
        // sort those)
        $sortable = $col->hasAttributeReference() ? $col->getSortable() : 'false';
        
        $output = '
                        title: "<span title=\"' . $this->buildHintText($col->getHint(), true) . '\">' . $col->getCaption() . '</span>"
                        ' . ($col->hasAttributeReference() ? ', field: "' . $col->getDataColumnName() . '", _attributeAlias: "' . $col->getAttributeAlias() . '"' : ', field: "' . $col->getId() . '"') . "
                        " . ($colspan ? ', colspan: ' . intval($colspan) : '') . ($rowspan ? ', rowspan: ' . intval($rowspan) : '') . "
                        " . ($col->isHidden() ? ', hidden: true' : '') . "
                        " . ($col->getWidth()->isTemplateSpecific() ? ', width: "' . $col->getWidth()->toString() . '"' : '') . "
                        " . (($format_options = $this->buildJsInitOptionsColumnFormatter($col, 'value', 'row', 'index')) ? ', ' . $format_options . '' : '') . "
                        " . ', align: "' . $this->buildCssTextAlignValue($col->getAlign()) . '"
                        ' . ', sortable: ' . $sortable . "
                        " . ($col->getSortable() ? ", order: '" . ($col->getDefaultSortingDirection() === SortingDirectionsDataType::ASC($this->getWorkbench()) ? 'asc' : 'desc') . "'" : '');
        
        return $output;
    }
    
    public function getToolbarId()
    {
        if (is_null($this->toolbar_id)) {
            $this->toolbar_id = $this->getId() . '_toolbar';
        }
        return $this->toolbar_id;
    }
    
    public function setToolbarId($value)
    {
        $this->toolbar_id = $value;
    }
    
    /**
     * Add JS code to be executed on the OnBeforeLoad event of jEasyUI datagrid.
     * The script will have access to the "param" variable
     * representing all XHR parameters to be sent to the server.
     *
     * @param string $script
     */
    public function addOnBeforeLoad($script)
    {
        $this->on_before_load .= $script;
    }
    
    /**
     * Set JS code to be executed on the OnBeforeLoad event of jEasyUI datagrid.
     * The script will have access to the "param" variable
     * representing all XHR parameters to be sent to the server.
     *
     * @param string $script
     */
    public function setOnBeforeLoad($script)
    {
        $this->on_before_load = $script;
    }
    
    /**
     * Binds a script to the onLoadSuccess event.
     *
     * @param string $script
     */
    public function addOnLoadSuccess($script)
    {
        $this->on_load_success .= $script;
    }
    
    protected function getOnLoadSuccess()
    {
        return $this->on_load_success;
    }
    
    /**
     * Binds a script to the onLoadError event.
     *
     * @param string $script
     */
    public function addOnLoadError($script)
    {
        $this->on_load_error .= $script;
    }
    
    protected function getOnLoadError()
    {
        return $this->on_load_error;
    }
    
    public function addOnChangeScript($string)
    {
        $this->addOnLoadSuccess($string);
        return parent::addOnChangeScript($string);
    }
    
    public function addLoadFilterScript($javascript)
    {
        $this->load_filter_script .= $javascript;
    }
    
    public function getLoadFilterScript()
    {
        return $this->load_filter_script;
    }
    
    public function buildJsDataLoaderWithoutAjax(DataSheetInterface $data)
    {
        $js = <<<JS
        
		try {
			var data = {$this->getTemplate()->encodeData($this->prepareData($data, false))};
		} catch (err){
            error();
			return;
		}
		
		var filter, value, total = data.rows.length;
        var filterPrefix = ("{$this->getTemplate()->getUrlFilterPrefix()}").toLowerCase();
		for(var p in param){
			if (p.toLowerCase().startsWith(filterPrefix)){
				column = p.substring(filterPrefix.length);
				value = param[p];
			}
			
			if (value){
				var regexp = new RegExp(value, 'i');
				for (var row=0; row<total; row++){
					if (data.rows[row] && typeof data.rows[row][column] !== 'undefined'){
						if (!data.rows[row][column].match(regexp)){
							data.rows.splice(row, 1);
						}
					}
				}
			}
		}
		data.total = data.rows.length;
        success(data);
		return;
JS;
        
        // This is a strange fix for jEasyUI rendering wrong height in non-ajax
        // data widgets...
        if (! $this->getWidget()->getHideHeader()){
            $this->addOnLoadSuccess("setTimeout(function(){ $('#" . $this->getId() . "').datagrid('resize'); }, 0);");
        }
        
        return $js;
    }
    
    public function buildJsInitOptions()
    {
        return $this->buildJsDataSource() . $this->buildJsInitOptionsHead();
    }
    
    protected function buildHtmlContextMenu()
    {
        $widget = $this->getWidget();
        $context_menu_html = '';
        if ($widget->hasButtons()) {
            $main_toolbar = $widget->getToolbarMain();
            
            foreach ($main_toolbar->getButtonGroupFirst()->getButtons() as $button) {
                $context_menu_html .= $this->buildHtmlContextMenuItem($button);
            }
            
            foreach ($widget->getToolbars() as $toolbar){
                if ($toolbar->getIncludeSearchActions()){
                    $search_button_group = $toolbar->getButtonGroupForSearchActions();
                } else {
                    $search_button_group = null;
                }
                foreach ($toolbar->getButtonGroups() as $btn_group){
                    if ($btn_group !== $main_toolbar->getButtonGroupFirst() && $btn_group !== $search_button_group && $btn_group->hasButtons()){
                        $context_menu_html = $context_menu_html ? $context_menu_html . '<div class="menu-sep"></div>' : $context_menu_html;
                        foreach ($btn_group->getButtons() as $button){
                            $context_menu_html .= $this->buildHtmlContextMenuItem($button);
                        }
                    }
                }
            }
        }
        return $context_menu_html;
    }
    
    protected function buildHtmlContextMenuItem(Button $button)
    {
        $menu_item = '';
        if ($button instanceof MenuButton){
            if ($button->getParent() instanceof ButtonGroup && $button === $this->getTemplate()->getElement($button->getParent())->getMoreButtonsMenu()){
                foreach ($button->getMenu()->getButtonGroups() as $grp){
                    $menu_item .= '<div class="menu-sep"></div>';
                    foreach ($grp->getButtons() as $btn){
                        $menu_item .= $this->buildHtmlContextMenuItem($btn);
                    }
                }
            } else {
                $menu_item .= '<div><span>' . $button->getCaption() . '</span><div>' . $this->getTemplate()->getElement($button)->buildHtmlMenuItems(). '</div></div>';
            }
        } else {
            $menu_item .= $this->getTemplate()->getElement($button)->buildHtmlButton();
        }
        $menu_item = str_replace(['<a id="', '</a>', 'easyui-linkbutton', ' href="#"'], ['<div id="' . $this->getId() . '_', '</div>', '', ''], $menu_item);
        return $menu_item;
    }
    
    protected function buildJsContextMenu()
    {
        // Prevent context menu on context menu. Otherwise the browser-menu keeps popping up
        // over the context menu from time to time.
        return '$("#' . $this->getId() . '_cmenu").contextmenu(function(e){e.stopPropagation(); e.preventDefault(); return false;})';
    }
    
    /**
     * Returns the base HTML element to construct the widget from: e.g. div, table, etc.
     *
     * @return string
     */
    protected function getBaseHtmlElement()
    {
        return 'table';
    }
    
    public function getDefaultButtonAlignment()
    {
        return $this->getTemplate()->getConfig()->getOption('WIDGET.DATA.DEFAULT_BUTTON_ALIGNMENT');
    }
    
    /**
     * Creates the HTML for the header controls: filters, sorters, buttons, etc.
     * @return string
     */
    protected function buildHtmlTableHeader($panel_options = "border: false, width: '100%'")
    {
        $widget = $this->getWidget();
        $toolbar_style = '';
        
        // Prepare the header with the configurator and the toolbars
        $configurator_widget = $widget->getConfiguratorWidget();
        /* @var $configurator_element \exface\JEasyUiTemplate\Templates\Elements\euiDataConfigurator */
        $configurator_element = $this->getTemplate()->getElement($this->getWidget()->getConfiguratorWidget())->setFitOption(false)->setStyleAsPills(true);
        
        if ($configurator_widget->isEmpty()){
            $configurator_widget->setHidden(true);
            $configurator_panel_collapsed = ', collapsed: true';
        }
        
        // jEasyUI will not resize the configurator once the datagrid is resized
        // (don't know why), so we need to do it manually.
        // Wrapping the resize-call into a setTimeout( ,0) is another strange
        // workaround, but if not done so, the configurator will get resized to
        // the old size, not the new one.
        $this->addOnResizeScript("
            if(typeof $('#" . $configurator_element->getId() . "')." . $configurator_element->getElementType() . "() !== 'undefined') {
                setTimeout(function(){
                    $('#" . $configurator_element->getId() . "')." . $configurator_element->getElementType() . "('resize');
                }, 0);
            }
        ");
        
        // Build the HTML for the button toolbars.
        // IMPORTANT: do it BEFORE the context menu since buttons may be moved
        // between toolbars and hidden in menus when rendering.
        $toolbars_html = $this->buildHtmlToolbars();
        
        // Create a context menu if any items were found
        $context_menu_html = $this->buildHtmlContextMenu();
        if ($context_menu_html && ($widget instanceof iHaveContextMenu) && $widget->getContextMenuEnabled()) {
            $context_menu_html = '<div id="' . $this->getId() . '_cmenu" class="easyui-menu">' . $context_menu_html . '</div>';
        } else {
            $context_menu_html = '';
        }
        
        if ($widget->getHideHeader()){
            $panel_options .= ', collapsed: true';
            $toolbar_style .= 'display: none; height: 0;';
        }
        
        return <<<HTML
        
                <div class="easyui-panel exf-data-header" data-options="footer: '#{$this->getToolbarId()}_footer', {$panel_options} {$configurator_panel_collapsed}">
                    {$configurator_element->buildHtml()}
                </div>
                <div id="{$this->getToolbarId()}_footer" class="datatable-toolbar" style="{$toolbar_style}">
                    {$toolbars_html}
                </div>
                {$context_menu_html}
                
HTML;
    }
    
    /**
     * Creates column options formatter:function(value,row,idx) and styler:function(value,row,idx) from the data
     * of a given column.
     *
     * The names of the JS variables "value", "row" and "index" must be passed along with with column widget.
     *
     * @param DataColumn $col
     * @param string $js_var_value
     * @param string $js_var_row
     * @param string $js_var_index
     * @return string
     */
    protected function buildJsInitOptionsColumnFormatter(DataColumn $col, $js_var_value, $js_var_row, $js_var_index)
    {
        $cellWidget = $col->getCellWidget();
        
        if (($cellWidget instanceof iDisplayValue) && $cellWidget->getDisableFormatting()) {
            return '';
        }
        
        $options = '';
        
        // Data type specific formatting
        $formatter_js = '';
        $cellTpl = $this->getTemplate()->getElement($cellWidget);
        if (($cellTpl instanceof JsValueDecoratingInterface) && $cellTpl->hasDecorator()) {
            $formatter_js = $cellTpl->buildJsValueDecorator($js_var_value);
        }
        
        // Formatter option
        if ($formatter_js) {
            $options = <<<JS

                        formatter: function({$js_var_value},{$js_var_row},{$js_var_index}){

                            try {
                                return {$formatter_js};
                            } catch (e) {
                                console.warn('Cannot apply decorator to column {$col->getDataColumnName()} . ', e);
                                return {$js_var_value}; 
                            } 

                        }
JS;
        }
        
        // Styler option
        $styler = $col->getCellStylerScript();
        if (! $styler && $cellWidget instanceof iShowText){
            switch ($cellWidget->getStyle()) {
                case TextStylesDataType::BOLD:
                    $styler = "return 'font-weight: bold;'";
                    break;
                case TextStylesDataType::ITALIC:
                    $styler = "return 'font-style: italic;'";
                    break;
                case TextStylesDataType::UNDERLINE:
                    $styler = "return 'text-decoration: underline;'";
                    break;
                case TextStylesDataType::UNDERLINE:
                    $styler = "return 'text-decoration: line-through;'";
                    break;
            }
        }
        
        if ($styler) {
            $options = ($options ?  $options . ', ' : '') . "styler: function({$js_var_value},{$js_var_row},{$js_var_index}){" . $styler . "}";
        }
        
        return $options;
    }
    
    protected function buildJsOnBeforeLoadScript($js_var_param = 'param')
    {
        return <<<JS
                    // General preparation
                    var jqself = $(this);
                    if (jqself.data("_skipNextLoad") == true) {
    					jqself.data("_skipNextLoad", false);
    					return false;
    				}

                    // Scripts added programmatically
				    {$this->on_before_load}
        
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
    
    protected function buildJsOnBeforeLoadFunction()
    {
        if (! $this->buildJsOnBeforeLoadScript()) {
            return '';
        }
        
        return <<<JS

                function(param) {
    				{$this->buildJsOnBeforeLoadScript('param')}
				}

JS;
    }
}
?>