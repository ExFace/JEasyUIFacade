<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\ProgressBar;
use exface\Core\Facades\AbstractAjaxFacade\Elements\HtmlProgressBarTrait;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryDisplayTrait;

/**
 *
 * @method ProgressBar getWidget()
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
}
?>