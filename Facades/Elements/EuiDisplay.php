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
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiValue::buildHtml()
     */
    public function buildHtml()
    {
        $widget = $this->getWidget();
        $expr = $widget->getValueExpression();
        $value = '';
        if ($expr !== null && ! $expr->isEmpty() && ! $expr->isReference()) {
            $value = $widget->getValueWithDefaults();
            $value = $this->escapeString(nl2br($value), false, true);
        }
        
        $element = <<<HTML

        <{$this->getElementType()} id="{$this->getId()}" style="{$this->buildCssElementStyle()}">{$value}</{$this->getElementType()}>

HTML;
        return $this->buildHtmlLabelWrapper($element);
    }
    
    public function buildCssElementClass()
    {
        return parent::buildCssElementClass() . ' exf-display';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiValue::getElementType()
     */
    public function getElementType() : ?string
    {
        return 'div';
    }
}