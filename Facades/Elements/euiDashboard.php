<?php
namespace exface\JEasyUIFacade\Facades\Elements;

class EuiDashboard extends EuiPanel
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
}