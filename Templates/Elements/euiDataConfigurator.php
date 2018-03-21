<?php
namespace exface\JEasyUiTemplate\Templates\Elements;

use exface\Core\Widgets\DataConfigurator;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryDataConfiguratorTrait;

/**
 * 
 * @method DataConfigurator getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class euiDataConfigurator extends euiTabs
{    
    use JqueryDataConfiguratorTrait;
    
    /**
     * Returns the default number of columns to layout this widget.
     *
     * @return integer
     */
    public function getDefaultColumnNumber()
    {
        return $this->getTemplate()->getConfig()->getOption("WIDGET.DATACONFIGURATOR.COLUMNS_BY_DEFAULT");
    }
    
    public function buildJs()
    {
        return parent::buildJs() . $this->buildJsRefreshOnEnter();
    }
}
?>
