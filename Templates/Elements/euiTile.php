<?php
namespace exface\JEasyUiTemplate\Templates\Elements;

/**
 * Tile-widget for JEasyUi-Template.
 *
 * @author SFL
 *
 */
class euiTile extends euiButton
{
    
    function buildHtml()
    {
        $widget = $this->getWidget();
        
        $icon_class = $widget->getIcon() && ! $widget->getHideButtonIcon() ? $this->buildCssIconClass($widget->getIcon()) : '';
        
        if ($this->getWidget()->hasAction()) {
            $click = "onclick=\"{$this->buildJsClickFunctionName()}();\"";
            $style = 'cursor: pointer;';
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
}
