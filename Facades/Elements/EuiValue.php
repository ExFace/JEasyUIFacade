<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Interfaces\Widgets\iTakeInput;
use exface\Core\Widgets\Value;
use exface\Core\Interfaces\Widgets\iLayoutWidgets;
use exface\Core\Widgets\WidgetGroup;
use exface\Core\Interfaces\Widgets\iContainOtherWidgets;
use exface\Core\DataTypes\WidgetVisibilityDataType;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryLiveReferenceTrait;

/**
 * Generates a <div> element for a Value widget and wraps it in a masonry grid item if needed.
 * 
 * @method Value getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class EuiValue extends EuiAbstractElement
{
    use JqueryLiveReferenceTrait;

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::init()
     */
    protected function init()
    {
        parent::init();
        
        // If the input's value is bound to another element via an expression, we need to make sure, that other element will
        // change the input's value every time it changes itself. This needs to be done on init() to make sure, the other element
        // has not generated it's JS code yet!
        if (! $this->getWidget()->isInTable()) {
            $this->registerLiveReferenceAtLinkedElement();
        }
        
        return;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::getElementType()
     */
    public function getElementType() : ?string
    {
        return $this->getCaption() ? 'span' : 'p';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildHtml()
     */
    public function buildHtml()
    {
        $widget = $this->getWidget();
        $expr = $widget->getValueExpression();
        $value = '';
        if (! $expr->isEmpty() && ! $expr->isReference()) {
            $value = $widget->getValueWithDefaults();
            $value = $this->escapeString(nl2br($widget->getValueWithDefaults()), false, true);
        }
        
        $output = <<<HTML

        <div id="{$this->getId()}" class="exf-value {$this->buildCssElementClass()}">{$value}</div>

HTML;
        return $this->buildHtmlGridItemWrapper($output);
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildCssHeightDefaultValue()
     */
    protected function buildCssHeightDefaultValue()
    {
        return 'auto';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::getCaption()
     */
    protected function getCaption() : string
    {
        $caption = parent::getCaption();
        if ($caption !== '' && ($this->getWidget() instanceof iTakeInput) === false && ($this->getWidget() instanceof iContainOtherWidgets) === false) {
            $caption .= ':';
        }
        return $caption;
    }
    
    /**
     * Adds a <label> tag to the given HTML code and wraps it in a masonry grid item if needed.
     * 
     * Set $make_grid_item to FALSE to disable wrapping in a grid item <div> - this way the
     * grid item can be generated in a custom way. Wrapping every label-control pair by default
     * is just a convenience function, so every facade element just needs to call one single
     * wrapper by default.
     * 
     * @param string $html
     * @param boolean $make_grid_item
     * 
     * @return string
     */
    protected function buildHtmlLabelWrapper($html, $make_grid_item = true)
    {
        if ($caption = $this->getCaption()) {
            // If there is a caption, add a <label> with a width of 40% of a single column.
            // Note: if the widget has a differen width, the label should still be as wide
            // as 40% of a single column to look nicely in forms with a mixture of single-size 
            // and larger widgets - e.g. default editors for actions, behaviors, etc.
            $labelStyle = '';
            $innerStyle = '';
            $width = $this->getWidget()->getWidth();
            if ($width->isRelative() === true) {
                if ($width->isMax() === true && $this->getWidget()->getParent() instanceof iLayoutWidgets) {
                    $parentEl = $this->getFacade()->getElement($this->getWidget()->getParent());
                    if (method_exists($parentEl, 'getNumberOfColumns')) {
                        $value = $parentEl->getNumberOfColumns();
                    } else {
                        $value = $this->getWidget()->getParent() ?? 1;
                    }
                } else {
                    $value = $width->getValue();
                }
                $labelStyle = " max-width: calc(40% / {$value} - 11px);";
                $innerStyle = " width: calc(100% - 100% / {$value} * 0.4 + 1px);";
            } else {
                $labelStyle .= " max-width: calc(40% - 11px);";
                $innerStyle .= " width: 60%;";
            }
            if ($this->getWidget()->isHidden()) {
                $labelClasses = 'exf-hidden';
                $innerClasses = 'exf-hidden';
            } else {
                $labelClasses = '';
                $innerClasses = '';
            }
            $html = <<<HTML

						<label style="{$labelStyle}" class="{$labelClasses}">{$caption}</label>
						<div class="exf-labeled-item {$innerClasses}" style="{$innerStyle}">{$html}</div>

HTML;
        }
        
        if ($make_grid_item) {
            $html = $this->buildHtmlGridItemWrapper($html, $this->getTooltip());
        }
        
        return $html;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildCssElementClass()
     */
    public function buildCssElementClass()
    {
        return parent::buildCssElementClass() . ' exf-control' . ($this->getWidget()->getVisibility() >= WidgetVisibilityDataType::PROMOTED ? ' promoted' : '');
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJs()
     */
    public function buildJs()
    {
        return '';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::buildJsSetHidden()
     */
    protected function buildJsSetHidden(bool $hidden) : string
    {
        return "$('#{$this->getId()}').parents('.exf-control').first()." . ($hidden ? 'hide()' : 'show()');
    }
}