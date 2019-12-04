<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\RangeFilter;
use exface\Core\Widgets\InlineGroup;
use exface\Core\Factories\WidgetFactory;
use exface\Core\CommonLogic\UxonObject;

/**
 * Creates and renders an InlineGroup with to and from filters.
 * 
 * @method Filter getWidget();
 * 
 * @author Andrej Kabachnik
 *
 */
class EuiRangeFilter extends EuiFilter
{
    private $inlineGroup = null;
    
    /**
     * 
     * @return InlineGroup
     */
    protected function getWidgetInlineGroup() : InlineGroup
    {
        if ($this->inlineGroup === null) {
            $widget = $this->getWidget();
            $wg = WidgetFactory::create($widget->getPage(), 'InlineGroup', $widget);
            $wg->setSeparator('-');
            
            $inputUxon = $widget->getInputWidget()->exportUxonObject();
            $inputUxon->setProperty('hide_caption', true);
            $filterFromUxon = new UxonObject([
                'widget_type' => 'Filter',
                'hide_caption' => true,
                'input_widget' => $inputUxon
            ]);
            $filterFromUxon->setProperty('comparator', $widget->getComparatorFrom());
            $filterTo = $filterFromUxon->copy();
            $filterTo->setProperty('comparator', $widget->getComparatorTo());
            
            if ($widget->hasValueFrom() === true) {
                $filterFromUxon->setProperty('value', $widget->getValueFrom());
            }
            if ($widget->hasValueTo() === true) {
                $filterTo->setProperty('value', $widget->getValueTo());
            }
            
            $groupWidgets = new UxonObject([
                $filterFromUxon,
                $filterTo
            ]);
            
            $wg->setWidgets($groupWidgets);
            $wg->setCaption($widget->getCaption());
            
            $this->inlineGroup = $wg;
        }
        return $this->inlineGroup;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiFilter::buildHtml()
     */
    public function buildHtml()
    {
        return $this->getFacade()->getElement($this->getWidgetInlineGroup())->buildHtml();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiFilter::buildJs()
     */
    public function buildJs()
    {
        return $this->getFacade()->getElement($this->getWidgetInlineGroup())->buildJs();
    }
    
    /**
     * 
     * @param string|null $valueJs
     * @return string
     */
    public function buildJsConditionGetter($valueJs = null)
    {
        $conditions = [];
        foreach ($this->getWidgetInlineGroup()->getWidgets() as $filter) {
            $filterEl = $this->getFacade()->getElement($filter);
            if (method_exists($filterEl, 'buildJsConditionGetter') === true) {
                $conditions[] = $filterEl->buildJsConditionGetter($valueJs);
            }
        }
        return implode(',', $conditions);
    }
}