<?php
namespace exface\JEasyUiTemplate\Templates\Elements;

use exface\Core\Widgets\Tiles;

/**
 * 
 * @method Tiles getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class euiTiles extends euiWidgetGrid
{
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiWidgetGrid::getNumberOfColumnsByDefault()
     */
    public function getNumberOfColumnsByDefault() : int
    {
        return $this->getTemplate()->getConfig()->getOption("WIDGET.TILECONTAINER.COLUMNS_BY_DEFAULT");
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildCssElementClass()
     */
    public function buildCssElementClass()
    {
        return 'exf-panel-flat eui-tiles ' . parent::buildCssElementClass();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiWidgetGrid::buildJsDataOptions()
     */
    public function buildJsDataOptions()
    {
        return parent::buildJsDataOptions() . ', border: false';
    }
}