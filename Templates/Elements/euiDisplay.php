<?php
namespace exface\JEasyUiTemplate\Templates\Elements;

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
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiValue::init()
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
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiValue::buildHtml()
     */
    public function buildHtml()
    {
        $widget = $this->getWidget();
        $value = nl2br($widget->getValue());
        
        $element = <<<HTML

        <{$this->getElementType()} id="{$this->getId()}" class="exf-display {$this->buildCssElementClass()}" style="{$this->buildCssElementStyle()}">{$value}</{$this->getElementType()}>

HTML;
        return $this->buildHtmlLabelWrapper($element);
    }
}
?>