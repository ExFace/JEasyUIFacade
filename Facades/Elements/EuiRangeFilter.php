<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\RangeFilter;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JsRangeFilterTrait;

/**
 * Creates and renders an InlineGroup with to and from filters.
 * 
 * @method RangeFilter getWidget();
 * 
 * @author Andrej Kabachnik
 *
 */
class EuiRangeFilter extends EuiFilter
{
    use JsRangeFilterTrait;
    
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