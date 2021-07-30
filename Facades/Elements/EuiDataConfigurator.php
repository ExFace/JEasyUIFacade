<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryDataConfiguratorTrait;
use exface\Core\Widgets\ButtonGroup;
use exface\Core\Factories\WidgetFactory;
use exface\Core\CommonLogic\UxonObject;
use exface\Core\CommonLogic\Constants\Icons;

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
    
    /**
     * Returns the default number of columns to layout this widget.
     *
     * @return integer
     */
    public function getNumberOfColumnsByDefault() : int
    {
        return $this->getFacade()->getConfig()->getOption("WIDGET.DATACONFIGURATOR.COLUMNS_BY_DEFAULT");
    }
    
    public function buildJs()
    {
        return parent::buildJs() . <<<JS

{$this->buildJsRefreshOnEnter()}
{$this->buildJsRegisterOnActionPerformed($this->buildJsRefreshConfiguredWidget(true))}

JS;
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