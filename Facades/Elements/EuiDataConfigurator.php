<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryDataConfiguratorTrait;
use exface\Core\Widgets\ButtonGroup;
use exface\Core\Factories\WidgetFactory;
use exface\Core\CommonLogic\UxonObject;
use exface\Core\CommonLogic\Constants\Icons;
use exface\Core\Interfaces\Widgets\iHaveContextMenu;

/**
 * 
 * @method \exface\Core\Widgets\DataConfigurator getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class EuiDataConfigurator extends EuiTabs
{    
    use JqueryDataConfiguratorTrait;
    
    private $btnCollaps = null;
    
    private $headerPanelId = null;
    
    public function buildHtml()
    {
        $html = parent::buildHtml();
        foreach ($this->getWidget()->getTabs() as $tab) {
            if ($tab->countWidgetsVisible() === 0) {
                $tab->setHidden(true);
            }
        }
        if ($this->getWidget()->countWidgetsVisible() === 0) {
            return '<div style="display: none">' . $html . '</div>';
        }
        return $html;
    }
    
    public function getIdOfHeaderPanel() : string
    {
        if (is_null($this->headerPanelId)) {
            $this->headerPanelId = $this->getId() . '_toolbar';
        }
        return $this->headerPanelId;
    }
    
    /**
     * Creates the HTML for the header controls: filters, sorters, buttons, etc.
     * @return string
     */
    public function buildHtmlHeaderPanel(string $toolbarHtml = '')
    {
        $configuredWidget = $this->getWidget()->getWidgetConfigured();
        $toolbar_style = '';
        $panel_options = "border: false";
        
        // Prepare the header with the configurator and the toolbars
        $configuratorWidget = $this->getWidget();
        
        // jEasyUI will not resize the configurator once the datagrid is resized
        // (don't know why), so we need to do it manually. The `setTimeout()` is
        // also important as without it the flexible width of the filters does not
        // work and they are rendered too small. The `doLayout` on the other hand
        // makes sure, that complex filter widgets (like RangeFilter) are rendered
        // correctly in panels, that are not visible right away - e.g. in secondary
        // tabs like in the default editor of `axenox.Deployer.project` in the tab
        // "Deployments".
        $this->getFacade()->getElement($configuredWidget)->addOnResizeScript("setTimeout(function(){
            $('#{$this->getIdOfHeaderPanel()}').find('.easyui-panel').panel('doLayout');
            {$this->getFacade()->getElement($configuratorWidget->getFilterTab())->buildJsLayouter()}
        },0);");
            
        if ($configuredWidget->getHideHeader()){
            $panel_options .= ', collapsed: true';
            $toolbar_style .= 'display: none; height: 0;';
        } else {
            if ($configuredWidget->getConfiguratorWidget()->isCollapsed() === true) {
                $panel_options .= ', collapsed: true';
            }
        }
        
        if ($configuredWidget->getHideHeader()){
            $header_style = 'visibility: hidden; height: 0px; padding: 0px;';
        }
        
        return <<<HTML
        
        <div id="{$this->getIdOfHeaderPanel()}" style="{$header_style}">
            <div class="easyui-panel exf-data-header" data-options="footer: '#{$this->getIdOfHeaderPanel()}_footer', {$panel_options}">
                {$this->getFacade()->getElement($configuratorWidget->getFilterTab())->buildHtml()}
            </div>
            <div id="{$this->getIdOfHeaderPanel()}_footer" class="datatable-toolbar" style="{$toolbar_style}">
                {$toolbarHtml}
            </div>
        </div>
                
HTML;
    }
    
    /**
     * Returns the default number of columns to layout this widget.
     *
     * @return integer
     */
    public function getNumberOfColumnsByDefault() : int
    {
        return $this->getFacade()->getConfig()->getOption("WIDGET.DATACONFIGURATOR.COLUMNS_BY_DEFAULT");
    }

    /**
     * {@inheritDoc}
     * @see EuiTabs::buildJs()
     */
    public function buildJs()
    {
        return parent::buildJs() . <<<JS

{$this->buildJsRegisterOnActionPerformed($this->buildJsRefreshConfiguredWidget(true))}
JS;
    }

    /**
     * Returns JS code required for the header of the panel-wrapper for the widget
     *
     * The panel header does not show the entire configurator, but rather just the contents of the filters
     * tab with some additional logic like refresh on Enter-key. So we do not need all the JS here. In
     * particular, no JS for sorters or widget setups.
     *
     * @return string
     */
    public function buildJsForPanelHeader()
    {
        return <<<JS

{$this->getFacade()->getElement($this->getWidget()->getFilterTab())->buildJs()}
{$this->buildJsRefreshOnEnter()}
{$this->buildJsRegisterOnActionPerformed($this->buildJsRefreshConfiguredWidget(true))}
JS;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiTabs::buildJsTabsInit()
     */
    protected function buildJsTabsInit() : string
    {
        // TODO initialize tabs here (or remove the method) once there really is a rendered configurator control
        return '';
    }
    
    public function addButtonToCollapseExpand(ButtonGroup $buttonGroup, int $position = 0, string $onFinishedJs = '')
    {
        $tableEl = $this->getFacade()->getElement($this->getWidget()->getWidgetConfigured());
        $collapseButtonId = 'headerCollapseButton_' . $tableEl->getId();
        $collapseButton = WidgetFactory::createFromUxon($this->getWidget()->getPage(), new UxonObject([
            'widget_type' => 'Button',
            'id' => $collapseButtonId,
            'action' => [
                'alias' => 'exface.Core.CustomFacadeScript'
            ],
            'icon' => $this->getWidget()->isCollapsed() === true ? Icons::CHEVRON_DOWN : Icons::CHEVRON_UP,
            'caption' => $this->translate('WIDGET.DATATABLE.CONFIGURATOR_EXPAND_COLLAPSE'),
            'align' => 'right',
            'hide_caption' => true
        ]), $buttonGroup);
        $buttonGroup->addButton($collapseButton, $position);
        
        // Give the script to the button AFTER being created to make sure eventual id spaces are appended to the
        // id, which is used inside the script. Otherwise the id will not match in dialogs with id spaces!
        $collapseButton->getAction()->setScript(<<<JS
            
    var toggleBtn = $('#{$this->getFacade()->getElement($collapseButton)->getId()}');
    var confPanel = toggleBtn.parents('.datatable-toolbar').prev();
    if (confPanel.css('display') === 'none') {
        confPanel.panel('expand');
        toggleBtn.find('.fa-chevron-down').removeClass('fa-chevron-down').addClass('fa-chevron-up');
    } else {
        confPanel.panel('collapse');
        toggleBtn.find('.fa-chevron-up').removeClass('fa-chevron-up').addClass('fa-chevron-down');
    }

    {$onFinishedJs}
    
JS);
        return $collapseButton;
    }
    
    /**
     *
     * @return string
     */
    protected function buildJsRefreshOnEnter()
    {
        // Use keyup() instead of keypress() because the latter did not work with jEasyUI combos.
        return <<<JS
        setTimeout(function(){
            $('#{$this->getFacade()->getElement($this->getWidget()->getFilterTab())->getId()}').find('input').keyup(function (ev) {
                var keycode = (ev.keyCode ? ev.keyCode : ev.which);
                if (keycode == '13') {
                    {$this->buildJsRefreshConfiguredWidget(false)};
                }
            })
        }, 10);
        
JS;
    }
}