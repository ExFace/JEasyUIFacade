<?php
namespace exface\JEasyUiTemplate\Templates\Elements;

use exface\Core\Widgets\ProgressBar;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\HtmlProgressBarTrait;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryDisplayTrait;

/**
 *
 * @method ProgressBar getWidget()
 *        
 * @author Andrej Kabachnik
 *        
 */
class euiProgressBar extends euiDisplay
{
    use HtmlProgressBarTrait;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiDisplay::buildHtml()
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