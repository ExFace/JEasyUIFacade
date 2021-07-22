<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\SlickGalleryTrait;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JsUploaderTrait;
use exface\Core\Widgets\Parts\Uploader;
use exface\Core\DataTypes\DateTimeDataType;
use exface\Core\Factories\DataTypeFactory;
use exface\Core\Facades\AbstractAjaxFacade\Formatters\JsDateFormatter;
use exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement;
use exface\Core\CommonLogic\DataSheets\DataColumn;

/**
 * Creates a jEasyUI Panel with a slick image slider for a DataimageGallery widget.
 * 
 * @author Andrej Kabachnik
 * 
 * @method \exface\Core\Widgets\Imagegallery getWidget()
 *        
 */
class EuiImageGallery extends EuiData
{    
    use SlickGalleryTrait;
    use JsUploaderTrait;
    
    public function buildHtmlHeadTags()
    {
        $headers = array_merge(parent::buildHtmlHeadTags(), $this->buildHtmlHeadTagsSlick());
        
        return $headers;
    }

    public function buildHtml()
    {
        $chart_panel_options = "title: '{$this->getCaption()}'";
        $this->addCarouselFeatureButtons($this->getWidget()->getToolbarMain()->getButtonGroupForSearchActions(), 1);
        $panel = <<<HTML

<div class="exf-grid-item {$this->getMasonryItemClass()} exf-imagecarousel" style="width:{$this->getWidth()};min-width:{$this->getMinWidth()};padding:{$this->getPadding()};box-sizing:border-box;">
    <div class="easyui-panel" style="height: auto; width: 100%" id="{$this->getIdOfSlick()}_wrapper" data-options="{$chart_panel_options}, onResize: function(){ {$this->getOnResizeScript()} }">
    	{$this->buildHtmlTableHeader()}
        <div style="height:{$this->getHeight()}; width: 100%">
    	   {$this->buildHtmlCarousel()}
        </div>
    </div>
</div>

HTML;
    
        return $panel;
    }

    function buildJs()
    {
        $widget = $this->getWidget();
        // Add Scripts for the configurator widget first as they may be needed for the others
        $configurator_element = $this->getFacade()->getElement($widget->getConfiguratorWidget());
        $output .= $configurator_element->buildJs();
        
        // Add scripts for the buttons
        $output .= $this->buildJsButtons();
        
        $output .= <<<JS
        
                    $('#{$configurator_element->getId()}').find('.grid').on('layoutComplete', function( event, items ) {
                        setTimeout(function(){
                            var newHeight = $('#{$this->getIdOfSlick()}_wrapper > .panel').height();
                            $('#{$this->getIdOfSlick()}').height($('#{$this->getIdOfSlick()}').parent().height()-newHeight);
                        }, 0);
                    });
                    
JS;
        return $output . <<<JS

    function {$this->buildJsFunctionPrefix()}_init(){
        {$this->buildJsSlickInit()}
    }
    
    function {$this->buildJsFunctionPrefix()}_load(){
    	{$this->buildJsDataSource()}
    }

    {$this->buildJsFunctionPrefix()}_init();
    setTimeout(function(){
        {$this->buildJsFunctionPrefix()}_load();
    }, 0);

JS;
    }
    
    /**
     *
     * {@inheritdoc}
     * @see AbstractJqueryElement::buildJsRefresh()
     */
    public function buildJsRefresh($keep_pagination_position = false)
    {
        return $this->buildJsFunctionPrefix() . "_load();";
    }

    /**
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiData::buildJsDataSource()
     */
    public function buildJsDataSource() : string
    {
        $widget = $this->getWidget();
        
        return <<<JS

    // Don't load if already loading    
    if ($('#{$this->getIdOfSlick()}').data('_loading')) return;

	{$this->buildJsBusyIconShow()}
	
    $('#{$this->getIdOfSlick()}').data('_loading', 1);

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
                var carousel = $('#{$this->getIdOfSlick()}');

                {$this->buildJsLoadFilterHandleWidgetLinks('json.rows')}
                    
                {$this->buildJsSlickSlidesFromData('carousel', 'json')}

                {$this->buildJsUploaderInit('carousel', 'l-btn-plain')}
                
		        {$this->buildJsBusyIconHide()}
		        $('#{$this->getIdOfSlick()}').data('_loading', 0);
			} catch (err) {
                console.error(err);
				{$this->buildJsBusyIconHide()}
			}
		},
		error: function(jqXHR, textStatus,errorThrown){
            {$this->buildJsBusyIconHide()}
	        $('#{$this->getIdOfSlick()}').data('_loading', 0);
            {$this->buildJsShowErrorAjax('jqXHR')}
		}
	});
	
JS;
    }
    
    /**
     *
     * @see SlickGalleryTrait::buildJsUploadSend()
     */
    protected function buildJsUploadSend(string $oParamsJs, string $onUploadCompleteJs) : string
    {
        return <<<JS

                {$this->buildJsLoadFilterHandleWidgetLinks('oParams.data')}                

                $.ajax({
                    url: "{$this->getAjaxUrl()}",
                    data: $oParamsJs,
                    method: 'POST',
                    success: function(json){
                        {$onUploadCompleteJs}
            		},
            		error: function(jqXHR, textStatus,errorThrown){
                        {$this->buildJsBusyIconHide()}
                        {$this->buildJsShowErrorAjax('jqXHR')}
                        {$this->buildJsRefresh(true)}
            		}
            	});

JS;
    }
    
    /**
     *
     * @return JsDateFormatter
     */
    protected function getDateFormatter() : JsDateFormatter
    {
        return new JsDateFormatter(DataTypeFactory::createFromString($this->getWorkbench(), DateTimeDataType::class));
    }
    
    /**
     * Generates the acceptedFileTypes option with a corresponding regular expressions if allowed_extensions is set
     * for the widget
     *
     * @return string
     */
    protected function buildJsUploadAcceptedFileTypesFilter()
    {
        $uploader = $this->getWidget()->getUploader();
        if ($uploader->getAllowedFileExtensions()) {
            return 'acceptFileTypes: /(\.|\/)(' . str_replace(array(
                ',',
                ' '
            ), array(
                '|',
                ''
            ), $uploader->getAllowedFileExtensions()) . ')$/i,';
        } else {
            return '';
        }
    }
		   
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::buildJsBusyIconShow()
     */
    public function buildJsBusyIconShow()
    {
        return $this->buildJsBusyIconHide() . "$('#{$this->getIdOfSlick()}').prepend('<div class=\"imagecarousel-loading\"><div class=\"datagrid-mask\" style=\"display:block\"></div><div class=\"datagrid-mask-msg\" style=\"display: block; left: 50%; height: 16px; margin-left: -107.555px; line-height: 16px;\"></div></div>');";
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::buildJsBusyIconHide()
     */
    public function buildJsBusyIconHide()
    {
        return "$('#{$this->getIdOfSlick()} .imagecarousel-loading').remove();";
    }
    
    /**
     * 
     * @see JsUploaderTrait::getUploader()
     */
    protected function getUploader() : Uploader
    {
        return $this->getWidget()->getUploader();
    }
}