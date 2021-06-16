<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\HtmlProgressBarTrait;

/**
 *
 * @method \exface\Core\Widgets\ProgressBar getWidget()
 *        
 * @author Andrej Kabachnik
 *        
 */
class EuiProgressBar extends EuiDisplay
{
    use HtmlProgressBarTrait;
    
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
            $progress = $widget->getProgress($val);
            $text = $widget->getText($val);
        } else {
            $progress = null;
            $text = null;
        }
        $bar = $this->buildHtmlProgressBar($val, $text, $progress, $widget->getColorForValue($val));
        return $this->buildHtmlLabelWrapper($bar);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiValue::buildJs()
     */
    public function buildJs()
    {
        return parent::buildJs()
        . $this->buildJsValueSetter($this->escapeString($this->getWidget()->getValueWithDefaults())) . ';';
    }
}