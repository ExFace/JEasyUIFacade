<?php
namespace exface\JEasyUiTemplate\Template\Elements;

use exface\Core\Widgets\DataConfigurator;
use exface\AbstractAjaxTemplate\Template\Elements\JqueryDataConfiguratorTrait;

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

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUiTemplate\Template\Elements\euiTabs::inheritsColumnNumber()
     */
    public function inheritsColumnNumber()
    {
        return false;
    }
}
?>
