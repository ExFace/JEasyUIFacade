<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\InlineGroup;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryContainerTrait;

/**
 * Renders a inline-widget similar to Display or Input with a caption, but with
 * multiple contained widgets instead of the single value next to the caption.
 *
 * @author Andrej Kabachnik
 *        
 * @method InlineGroup getWidget()
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
        $noWidthIndexes = [];
        $setWidths = '';
        foreach ($this->getWidget()->getWidgets() as $idx => $subw) {
            if ($subw->getWidth()->isUndefined() === true) {
                $noWidthIndexes[] = $idx;
            } else {
                $setWidths .= ' - ' . $subw->getWidth()->getValue();
            }
        }
        if ($setWidths !== '') {
            $setWidths = '(100% ' . trim($setWidths) . ')';
        } 
        $noWidthCnt = count($noWidthIndexes);
        foreach ($noWidthIndexes as $i => $idx) {
            $subw = $this->getWidget()->getWidget($idx);
            if ($setWidths !== '') {
                $widthCalcCss = $setWidths . ' / ' . $noWidthCnt;
            } else {
                $widthCalcCss = round(100 / $noWidthCnt, 0) . '%';
            }
            if ($i === $noWidthCnt - 1) {
                $widthCalcCss .= ' - 4px';
            }
            $subw->setWidth("calc($widthCalcCss)");
        }
        return;
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