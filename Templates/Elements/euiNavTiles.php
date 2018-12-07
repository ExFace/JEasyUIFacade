<?php
namespace exface\JEasyUiTemplate\Templates\Elements;

use exface\Core\Widgets\NavTiles;

/**
 * 
 * @method NavTiles getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class euiNavTiles extends euiWidgetGrid 
{
    public function buildHtml()
    {
        if ($this->getWidget()->countWidgets() > 1) {
            foreach ($this->getWidget()->getWidgets() as $tiles) {
                $tiles->setNumberOfColumns(1);
            }
        } else {
            $this->getWidget()->getWidgetFirst()->setHideCaption(true);
        }
        return parent::buildHtml();
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiWidgetGrid::getDefaultColumnNumber()
     */
    public function getDefaultColumnNumber()
    {
        return $this->getTemplate()->getConfig()->getOption("WIDGET.NAVTILES.COLUMNS_BY_DEFAULT");
    }
}