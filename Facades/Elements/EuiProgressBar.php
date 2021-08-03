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
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiValue::buildJs()
     */
    public function buildJs()
    {
        return parent::buildJs()
        . $this->buildJsValueSetter($this->escapeString($this->getWidget()->getValueWithDefaults())) . ';';
    }
}