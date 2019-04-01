<?php
namespace exface\JEasyUIFacade\Facades\Elements;

/**
 * Tile-widget for JEasyUi-Facade.
 *
 * @author SFL
 *
 */
class EuiTile extends EuiButton
{
    
    function buildHtml()
    {
        $widget = $this->getWidget();
        
        $icon_class = $widget->getIcon() && $widget->getShowIcon(true) ? $this->buildCssIconClass($widget->getIcon()) : '';
        $style = $this->buildCssElementStyle();
        
        if ($this->getWidget()->hasAction()) {
            $click = "onclick=\"{$this->buildJsClickFunctionName()}();\"";
            $style .= 'cursor: pointer;';
        }
        
        $output = <<<JS

                <div id="{$this->getId()}" class="exf-tile-box" {$click} style="{$style}" title="{$widget->getHint()}">
                    <h3>{$widget->getTitle()}</h3>
   					<p>{$widget->getSubtitle()}</p>
            		<div class="exf-tile-icon">
            			<i class="{$icon_class}"></i>
            		</div>
    			</div>
JS;
        
        return $this->buildHtmlGridItemWrapper($output);
    }
    
    protected function buildCssHeightDefaultValue()
    {
        return $this->getHeightRelativeUnit() * 3 . 'px';
    }
    
    public function getMinWidth()
    {
        return 0;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildCssElementStyle()
     */
    public function buildCssElementStyle()
    {
        $style = '';
        $bgColor = $this->getWidget()->getColor();
        if ($bgColor !== null && $bgColor !== '') {
            $style .= 'background-color:' . $bgColor . ';';
        }
        return $style;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildCssElementClass()
     */
    public function buildCssElementClass()
    {
        return 'exf-tile ' . parent::buildCssElementClass();
    }
}
