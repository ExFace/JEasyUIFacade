<?php
namespace exface\JEasyUiTemplate\Template\Elements;

use exface\Core\Templates\AbstractAjaxTemplate\Interfaces\JsValueDecoratingInterface;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryDisplayTrait;

/**
 * @method Display getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class euiDisplay extends euiValue implements JsValueDecoratingInterface
{
    use JqueryDisplayTrait;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUiTemplate\Template\Elements\euiValue::init()
     */
    protected function init()
    {
        parent::init();
        $this->setElementType($this->getCaption() ? 'span' : 'div');
        return;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUiTemplate\Template\Elements\euiValue::generateHtml()
     */
    public function generateHtml()
    {
        $widget = $this->getWidget();
        $value = nl2br($widget->getValue());
        
        $element = <<<HTML

        <{$this->getElementType()} id="{$this->getId()}" class="exf-display {$this->buildCssElementClass()}">{$value}</{$this->getElementType()}>

HTML;
        return $this->buildHtmlLabelWrapper($element);
    }
}
?>