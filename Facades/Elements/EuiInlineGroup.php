<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryContainerTrait;
use exface\Core\Interfaces\WidgetInterface;

/**
 * Renders a inline-widget similar to Display or Input with a caption, but with
 * multiple contained widgets instead of the single value next to the caption.
 *
 * @author Andrej Kabachnik
 *        
 * @method \exface\Core\Widgets\InlineGroup getWidget()
 */
class EuiInlineGroup extends EuiValue
{
    use JqueryContainerTrait;
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiValue::init()
     */
    protected function init()
    {
        parent::init();
        $this->setElementType('div');
        if ($this->getWidget()->isStretched()) {
            $this->optimizeChildrenWidths();
        }
        return;
    }
    
    /**
     * Calculates widths for groupe widgets, that do not have a width set explicitly.
     * 
     * Widgets within the group, that have a width set explicitly retain it. The remaining
     * width is distributed evenly between those, that don't have a width yet.
     * 
     * @return EuiInlineGroup
     */
    protected function optimizeChildrenWidths() : EuiInlineGroup
    {
        $noWidthIndexes = []; // indexes of child widgets, that have no width
        $setWidthsCalcTotal = ''; // content of CSS calc() expression for all explicitly set widths
        $totalChildrenPadding = 0;
        
        // First look through all child widgets an gather width values and no-width indexes
        foreach ($this->getWidget()->getWidgets() as $idx => $subw) {
            if ($subw->getWidth()->isUndefined() === true) {
                $noWidthIndexes[] = $idx;
            } else {
                $setWidthsCalcTotal .= ' - ' . $subw->getWidth()->getValue();
            }
            $totalChildrenPadding += $this->getChildPadding($subw);
        }
        if ($setWidthsCalcTotal !== '') {
            $setWidthsCalcTotal = '(100% ' . trim($setWidthsCalcTotal) . ')';
        }
        
        // Since the padding can only be subtracted from no-width children, calculate
        // the average value to subtract
        $noWidthCnt = count($noWidthIndexes);
        $noWidthChildPadding = $totalChildrenPadding / $noWidthCnt;
        
        // Give every no-width widget a width
        foreach ($noWidthIndexes as $idx) {
            $subw = $this->getWidget()->getWidget($idx);
            if ($setWidthsCalcTotal !== '') {
                $widthCalcCss = $setWidthsCalcTotal . ' / ' . $noWidthCnt;
            } else {
                $widthCalcCss = round(100 / $noWidthCnt, 0) . '%';
            }
            $widthCalcCss .= " - {$noWidthChildPadding}px";
            $subw->setWidth("calc($widthCalcCss)");
        }
        return $this;
    }
    
    /**
     * Returns the padding the given child in pixels.
     * 
     * Currently every widget has a right-padding of 4px except for the last widget.
     * 
     * @return int
     */
    protected function getChildPadding(WidgetInterface $child) : int
    {
        $group = $this->getWidget();
        if ($child === $group->getWidget($group->countWidgets()-1)) {
            return 0;
        }
        return 4;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiValue::buildHtml()
     */
    public function buildHtml()
    {
        return $this->buildHtmlLabelWrapper($this->buildHtmlForWidgets());
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiValue::buildCssElementClass()
     */
    public function buildCssElementClass()
    {
        return parent::buildCssElementClass() . ' exf-inline-group exf-display';
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJs()
     */
    public function buildJs()
    {
        return $this->buildJsForWidgets();
    }
}