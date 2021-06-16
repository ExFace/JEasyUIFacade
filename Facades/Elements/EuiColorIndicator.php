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
        $indicator = $this->buildHtmlIndicator($val, $val, $widget->getColorForValue($val));
        return $this->buildHtmlLabelWrapper($indicator);
    }    
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiValue::buildJs()
     */
    public function buildJs()
    {
        return parent::buildJs() 
                . $this->buildJsValueSetter($this->escapeString($this->getWidget()->getValueWithDefaults()));
    }
}