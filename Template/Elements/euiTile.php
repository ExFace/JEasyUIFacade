<?php
namespace exface\JEasyUiTemplate\Template\Elements;

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
        
        $output = <<<JS

                <div id="{$this->getId()}" class="exf-tile-box">
                    <h3>{$widget->getTitle()}</h3>
   					<p>{$widget->getSubtitle()}</p>
            		<div class="exf-tile-icon">
            			<i class="{$icon_class}"></i>
            		</div>
            		<a href="javascript:void(0)" onclick="{$this->buildJsClickFunctionName()}();" class="exf-tile-footer">Start <i class="fa fa-arrow-circle-right"></i></a>
    			</div>
JS;
        
        return $this->buildHtmlGridItemWrapper($output);
    }
    
    protected function buildCssHeightDefaultValue()
    {
        return $this->getHeightRelativeUnit() * 3.2 . 'px';
    }
}
