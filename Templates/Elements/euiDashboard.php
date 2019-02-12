<?php
namespace exface\JEasyUiTemplate\Templates\Elements;

class euiDashboard extends euiPanel
{
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiWidgetGrid::getNumberOfColumnsByDefault()
     */
    public function getNumberOfColumnsByDefault() : int
    {
        return $this->getTemplate()->getConfig()->getOption("WIDGET.DASHBOARD.COLUMNS_BY_DEFAULT");
    }
}