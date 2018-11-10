<?php
namespace exface\JEasyUiTemplate\Templates\Elements;

use exface\Core\Widgets\ProgressBar;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\HtmlProgressBarTrait;

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
        return $this->buildHtmlProgressBar($this->getValueWithDefaults());
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiValue::buildJs()
     */
    public function buildJs()
    {
        return '';
    }
}
?>