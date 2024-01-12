<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JsSpinnerFilterTrait;

/**
 * Creates and renders an InlineGroup with the filter input and +/- buttons.
 * 
 * @method \exface\Core\Widgets\SpinnerFilter getWidget();
 * 
 * @author Andrej Kabachnik
 *
 */
class EuiSpinnerFilter extends EuiFilter
{
    use JsSpinnerFilterTrait;
    
    protected function buildCssWidthOfStepButton() : string
    {
        return '26px';
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiFilter::buildHtml()
     */
    public function buildHtml()
    {
        return $this->getFacade()->getElement($this->getWidgetInlineGroup())->buildHtml();
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiFilter::buildJs()
     */
    public function buildJs()
    {
        return $this->getFacade()->getElement($this->getWidgetInlineGroup())->buildJs();
    }
}