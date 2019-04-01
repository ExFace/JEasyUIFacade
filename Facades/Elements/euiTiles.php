<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\Tiles;

/**
 * 
 * @method Tiles getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class EuiTiles extends EuiWidgetGrid
{
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiWidgetGrid::getNumberOfColumnsByDefault()
     */
    public function getNumberOfColumnsByDefault() : int
    {
        return $this->getFacade()->getConfig()->getOption("WIDGET.TILECONTAINER.COLUMNS_BY_DEFAULT");
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildCssElementClass()
     */
    public function buildCssElementClass()
    {
        return 'exf-panel-flat Eui-tiles ' . parent::buildCssElementClass();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiWidgetGrid::buildJsDataOptions()
     */
    public function buildJsDataOptions()
    {
        return parent::buildJsDataOptions() . ', border: false';
    }
}