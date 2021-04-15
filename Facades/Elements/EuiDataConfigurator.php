<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryDataConfiguratorTrait;

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
        return parent::buildJs() . $this->buildJsRefreshOnEnter() . $this->buildJsRegisterOnActionPerformed($this->buildJsRefreshConfiguredWidget(true));
    }
}
?>
