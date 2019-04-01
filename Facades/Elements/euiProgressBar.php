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
        $bar = $this->buildHtmlProgressBar($val, $widget->getText($val), $widget->getProgress($val), $widget->getColor($val));
        return $this->buildHtmlLabelWrapper($bar);
    }
}
?>