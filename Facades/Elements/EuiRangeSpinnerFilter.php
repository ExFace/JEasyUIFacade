<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JsRangeSpinnerFilterTrait;

/**
 * Creates and renders an InlineGroup with to and from filters and +/- buttons.
 * 
 * @method \exface\Core\Widgets\RangeSpinnerFilter getWidget();
 * 
 * @author Andrej Kabachnik
 *
 */
class EuiRangeSpinnerFilter extends EuiRangeFilter
{
    use JsRangeSpinnerFilterTrait;
    
    protected function buildCssWidthOfStepButton() : string
    {
        return '18px';
    }
    
    protected function buildCssWidthOfRangeSeparator() : string
    {
        return '10px';
    }
}