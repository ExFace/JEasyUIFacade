<?php
namespace exface\JEasyUIFacade\Facades\Elements;

/**
 * 
 * @author Andrej Kabachnik
 * 
 * @method \exface\Core\Widgets\WidgetGroup getWidget()
 *
 */
class EuiWidgetGroup extends EuiPanel
{    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiPanel::buildJsDataOptions()
     */
    public function buildJsDataOptions()
    {
        return parent::buildJsDataOptions() . ', border: false';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiWidgetGrid::getFitOption()
     */
    public function getFitOption() : bool
    {
        return true;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildCssElementClass()
     */
    public function buildCssElementClass()
    {
        $classes = 'exf-panel-flat exf-widget-group';
        $widget = $this->getWidget();
        if ($widget->getHeight()->isUndefined() && ! $widget->isFilledBySingleWidget() && $this->getNumberOfColumns() === 1) {
            $classes .= ' exf-autoheight';
        }
        return parent::buildCssElementClass() . ' ' . $classes;
    }
}