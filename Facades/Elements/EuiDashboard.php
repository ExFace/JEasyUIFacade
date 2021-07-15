<?php
namespace exface\JEasyUIFacade\Facades\Elements;

class EuiDashboard extends EuiWidgetGrid
{
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiWidgetGrid::getNumberOfColumnsByDefault()
     */
    public function getNumberOfColumnsByDefault() : int
    {
        return $this->getFacade()->getConfig()->getOption("WIDGET.DASHBOARD.COLUMNS_BY_DEFAULT");
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiWidgetGrid::buildCssElementClass()
     */
    public function buildCssElementClass()
    {
        return 'exf-dashboard';
    }
}