<?php
namespace exface\JEasyUiTemplate\Templates\Elements;

use exface\Core\DataTypes\BooleanDataType;
use exface\Core\DataTypes\DateDataType;
use exface\Core\DataTypes\PriceDataType;
use exface\Core\DataTypes\NumberDataType;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\JquerySlickGalleryTrait;
use exface\Core\Widgets\DataImageGallery;
use exface\Core\DataTypes\UrlDataType;

/**
 * Creates a jEasyUI Panel with a slick image slider for a DataimageGallery widget.
 * 
 * @author Andrej Kabachnik
 * 
 * @method DataImageGallery getWidget()
 *        
 */
class euiDataImageGallery extends euiData
{    
    use JquerySlickGalleryTrait;
    
    public function buildHtmlHeadTags()
    {
        return array_merge(parent::buildHtmlHeadTags(), $this->buildHtmlHeadSliderIncludes());
    }

    public function buildHtml()
    {
        $chart_panel_options = ", title: '{$this->getCaption()}'";
        
        $panel = <<<HTML

<div class="exf-grid-item {$this->getMasonryItemClass()} exf-imagecarousel" style="width:{$this->getWidth()};min-width:{$this->getMinWidth()};height:{$this->getHeight()};padding:{$this->getPadding()};box-sizing:border-box;">
    <div class="easyui-panel" style="height: auto;" id="{$this->getId()}_wrapper" data-options="fit: true {$chart_panel_options}, onResize: function(){ {$this->getOnResizeScript()} }">
    	{$this->buildHtmlTableHeader()}
    	{$this->buildHtmlCarousel()}
    </div>
</div>

HTML;
    
        return $panel;
    }
    
    

    function buildJs()
    {
        $widget = $this->getWidget();
        // Add Scripts for the configurator widget first as they may be needed for the others
        $configurator_element = $this->getTemplate()->getElement($widget->getConfiguratorWidget());
        $output .= $configurator_element->buildJs();
        
        // Add scripts for the buttons
        $output .= $this->buildJsButtons();
        
        $output .= <<<JS
        
                    $('#{$configurator_element->getId()}').find('.grid').on( 'layoutComplete', function( event, items ) {
                        setTimeout(function(){
                            var newHeight = $('#{$this->getId()}_wrapper > .panel').height();
                            $('#{$this->getId()}').height($('#{$this->getId()}').parent().height()-newHeight);
                        }, 0);
                    });
                    
JS;
        return $output . <<<JS

{$this->buildJsCarouselFunctions()}
{$this->buildJsCarouselInit()}

JS;
    }

    public function buildJsDataSource() : string
    {
        $widget = $this->getWidget();
        
        if (($urlType = $widget->getImageUrlColumn()->getDataType()) && $urlType instanceof UrlDataType) {
            $base = $urlType->getBaseUrl();
        }
        
        return <<<JS
    
    // Don't load if already loading    
    if ($('#{$this->getId()}').data('_loading')) return;

	{$this->buildJsBusyIconShow()}
	
    $('#{$this->getId()}').data('_loading', 1);

	var param = {
       action: '{$widget->getLazyLoadingActionAlias()}',
	   resource: "{$widget->getPage()->getAliasWithNamespace()}",
	   element: "{$widget->getId()}",
	   object: "{$widget->getMetaObject()->getId()}"
    };

    var checkOnBeforeLoad = function(param){
        {$this->buildJsOnBeforeLoadScript('param')}
        {$this->buildJsOnBeforeLoadAddConfiguratorData('param')}
    }(param);

    if (checkOnBeforeLoad === false) {
        {$this->buildJsBusyIconHide()}
        return;
    }
	
	$.ajax({
       url: "{$this->getAjaxUrl()}",
       data: param,
       method: 'POST',
       success: function(json){
			try {
				var data = json.rows;
                var carousel = $('#{$this->getId()}');
                var src = '';
                var title = '';
				for (var i in data) {
                    src = '{$base}' + data[i]['{$widget->getImageUrlColumn()->getDataColumnName()}'];
                    title = data[i]['{$widget->getImageTitleColumn()->getDataColumnName()}'];
                    carousel.slick('slickAdd', '<div class="imagecarousel-item"><img src="' + src + '" title="' + title + '" alt="' + title + '" /></div>');
                }
		        {$this->buildJsBusyIconHide()}
		        $('#{$this->getId()}').data('_loading', 0);
			} catch (err) {
                console.error(err);
				{$this->buildJsBusyIconHide()}
			}
		},
		error: function(jqXHR, textStatus,errorThrown){
		   {$this->buildJsBusyIconHide()}
		   {$this->buildJsShowError('jqXHR.responseText', 'jqXHR.status + " " + jqXHR.statusText')}
		}
	});
	
JS;
    }
		   
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiAbstractElement::buildJsBusyIconShow()
     */
    public function buildJsBusyIconShow()
    {
        return "$('#{$this->getId()}').prepend('<div class=\"panel-loading\" style=\"height: 15px;\"></div>');";
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiAbstractElement::buildJsBusyIconHide()
     */
    public function buildJsBusyIconHide()
    {
        return "$('#{$this->getId()} .panel-loading').remove();";
    }

    /**
     * Returns a JS snippet, that empties the table (removes all rows).
     *
     * @return string
     */
    protected function buildJsDataResetter() : string
    {
        return <<<JS

           $('#{$this->getId()} .slick-track').empty();

JS;
    }
}