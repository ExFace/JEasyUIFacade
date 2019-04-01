<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Interfaces\JsValueDecoratingInterface;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryDisplayTrait;

/**
 * @method Display getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class EuiDisplay extends EuiValue implements JsValueDecoratingInterface
{
    use JqueryDisplayTrait;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiValue::init()
     */
    protected function init()
    {
        parent::init();
        $this->setElementType($this->getCaption() ? 'span' : 'div');
        $this->setElementType('div');
        return;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiValue::buildHtml()
     */
    public function buildHtml()
    {
        $widget = $this->getWidget();
        $value = nl2br($widget->getValue());
        
        $element = <<<HTML

        <{$this->getElementType()} id="{$this->getId()}" style="{$this->buildCssElementStyle()}">{$value}</{$this->getElementType()}>

HTML;
        return $this->buildHtmlLabelWrapper($element);
    }
    
    public function buildCssElementClass()
    {
        return parent::buildCssElementClass() . ' exf-display';
    }
}
?>