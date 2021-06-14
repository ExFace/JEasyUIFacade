<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\HtmlColorIndicatorTrait;

/**
 * Creates colored <div> from ColorIndicator widgets.
 * 
 * @method \exface\Core\Widgets\ColorIndicator getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class EuiColorIndicator extends EuiDisplay
{
    use HtmlColorIndicatorTrait;
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiDisplay::buildHtml()
     */
    public function buildHtml()
    {
        $widget = $this->getWidget();
        $val = $widget->getValueWithDefaults();
        if ($val !== null) {
            $text = $widget->getText($val);
        } else {
            $text = null;
        }
        $bar = $this->buildHtmlColorIndicator($val, $text, $widget->getColorForValue($val));
        return $this->buildHtmlLabelWrapper($bar);
    }    
}