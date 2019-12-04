<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Interfaces\Widgets\iTakeInput;
use exface\Core\Widgets\Value;
use exface\Core\Interfaces\Widgets\iLayoutWidgets;
use exface\Core\Widgets\WidgetGroup;
use exface\Core\Interfaces\Widgets\iContainOtherWidgets;

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
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::init()
     */
    protected function init()
    {
        parent::init();
        $this->setElementType($this->getCaption() ? 'span' : 'p');
        return;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildHtml()
     */
    public function buildHtml()
    {
        $value = nl2br($this->getWidget()->getValue());
        
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
                $labelStyle = " max-width: calc(40% / {$value} - 10px);";
                $innerStyle = " width: calc(100% - 100% / {$value} * 0.4 + 1px);";
            } else {
                $labelStyle .= " max-width: calc(40% - 10px);";
                $innerStyle .= " width: 60%;";
            }
            $html = '
						<label style="' . $labelStyle . '">' . $caption . '</label>
						<div class="exf-labeled-item" style="' . $innerStyle . '">' . $html . '</div>';
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
        return parent::buildCssElementClass() . ' exf-control';
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
     * Returns an inline JS-snippet to hide the entire widget (including label, etc.).
     * 
     * @return string
     */
    public function buildJsHideWidget() : string
    {
        return "$('#{$this->getId()}').parents('.exf-control').first().hide()";
    }
    
    /**
     * Returns an inline JS-snippet to show a previously hidden widget.
     *
     * @return string
     */
    public function buildJsShowWidget() : string
    {
        return "$('#{$this->getId()}').parents('.exf-control').first().show()";
    }
}
?>