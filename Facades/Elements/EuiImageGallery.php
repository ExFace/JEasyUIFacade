<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\SlickGalleryTrait;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JsUploaderTrait;
use exface\Core\Widgets\Parts\Uploader;
use exface\Core\DataTypes\DateTimeDataType;
use exface\Core\Factories\DataTypeFactory;
use exface\Core\Facades\AbstractAjaxFacade\Formatters\JsDateFormatter;

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
        $headers = array_merge(parent::buildHtmlHeadTags(), $this->buildHtmlHeadSliderIncludes());
        
        if ($this->getWidget()->isUploadEnabled()) {
            // The jQuery UI widget factory, can be omitted if jQuery UI is already included -->
            $headers[] = '<script src="vendor/bower-asset/blueimp-file-upload/js/vendor/jquery.ui.widget.js"></script>';
            // The Load Image plugin is included for the preview images and image resizing functionality -->
            $headers[] = '<script src="vendor/npm-asset/blueimp-load-image/js/load-image.all.min.js"></script>';
            // The Iframe Transport is required for browsers without support for XHR file uploads -->
            $headers[] = '<script src="vendor/bower-asset/blueimp-file-upload/js/jquery.iframe-transport.js"></script>';
            // The basic File Upload plugin -->
            $headers[] = '<script src="vendor/bower-asset/blueimp-file-upload/js/jquery.fileupload.js"></script>';
            // The File Upload processing plugin -->
            $headers[] = '<script src="vendor/bower-asset/blueimp-file-upload/js/jquery.fileupload-process.js"></script>';
            // The File Upload image preview & resize plugin -->
            $headers[] = '<script src="vendor/bower-asset/blueimp-file-upload/js/jquery.fileupload-image.js"></script>';
            // The File Upload audio preview plugin -->
            $headers[] = '<script src="vendor/bower-asset/blueimp-file-upload/js/jquery.fileupload-audio.js"></script>';
            // The File Upload video preview plugin -->
            $headers[] = '<script src="vendor/bower-asset/blueimp-file-upload/js/jquery.fileupload-video.js"></script>';
            // The File Upload validation plugin -->
            $headers[] = '<script src="vendor/bower-asset/blueimp-file-upload/js/jquery.fileupload-validate.js"></script>';
            $headers[] = '<script src="vendor/bower-asset/paste.js/paste.js"></script>';
            $headers = array_merge($headers, $this->getDateFormatter()->buildHtmlHeadIncludes($this->getFacade()), $this->getDateFormatter()->buildHtmlBodyIncludes($this->getFacade()));
        }
        
        return $headers;
    }

    public function buildHtml()
    {
        $chart_panel_options = "title: '{$this->getCaption()}'";
        $this->addCarouselFeatureButtons($this->getWidget()->getToolbarMain()->getButtonGroupForSearchActions(), 1);
        $panel = <<<HTML

<div class="exf-grid-item {$this->getMasonryItemClass()} exf-imagecarousel" style="width:{$this->getWidth()};min-width:{$this->getMinWidth()};padding:{$this->getPadding()};box-sizing:border-box;">
    <div class="easyui-panel" style="height: auto; width: 100%" id="{$this->getId()}_wrapper" data-options="{$chart_panel_options}, onResize: function(){ {$this->getOnResizeScript()} }">
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

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiData::buildJsDataSource()
     * @see SlickGalleryTrait::buildJsDataSource()
     */
    public function buildJsDataSource() : string
    {
        $widget = $this->getWidget();
        
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
                var carousel = $('#{$this->getId()}');

                {$this->buildJsLoadFilterHandleWidgetLinks('json.rows')}
                    
                {$this->buildJsCarouselSlidesFromData('carousel', 'json')}

                {$this->buildJsUploaderInit('carousel')}
                
		        {$this->buildJsBusyIconHide()}
		        $('#{$this->getId()}').data('_loading', 0);
			} catch (err) {
                console.error(err);
				{$this->buildJsBusyIconHide()}
			}
		},
		error: function(jqXHR, textStatus,errorThrown){
            {$this->buildJsBusyIconHide()}
	        $('#{$this->getId()}').data('_loading', 0);
            {$this->buildJsShowErrorAjax('jqXHR')}
		}
	});
	
JS;
    }
    
    /**
     * TODO move this method to a JquerFileUploaderTrait
     * 
     * @param string $jqSlickJs
     * @return string
     */
    protected function buildJsUploaderInit(string $jqSlickJs) : string
    {
        if ($this->getWidget()->isUploadEnabled() === false) {
            return '';
        }
        
        $widget = $this->getWidget();
        $uploader = $this->getWidget()->getUploader();
        
        $fileModificationColumnJs = '';
        if ($uploader->hasFileLastModificationTimeAttribute()) {
            $fileModificationColumnJs = "{$widget->getMimeTypeColumn()->getDataColumnName()}: file.lastModified,";
        }
        
        $maxFileSizeInBytes = $uploader->getMaxFileSizeMb()*1024*1024;
        
        // TODO Use built-in file uploading instead of a custom $.ajax request to
        // be able to take advantage of callbacks like fileuploadfail, fileuploadprogressall
        // etc. To get the files from the XHR on server-side, we could replace their names
        // by the corresponding data column names and teach the data reader middleware to
        // place $_FILES in the data sheet if the column names match.
        
        $output = <<<JS

    $jqSlickJs.slick('slickAdd', '<a class="imagecarousel-upload pastearea l-btn-plain"><i class="fa fa-upload"></i></a>');
    $jqSlickJs.find('.imagecarousel-upload').on('click', function(){
        var jqA = $(this);
        if (! jqA.hasClass('armed')) {
            jqA.addClass('armed');
            jqA.children('.fa-upload').hide();
            jqA.append('<span>Paste or drag file here</span>');
        } else {
            jqA.removeClass('armed');
            jqA.children('span').remove();
            jqA.children('.fa-upload').show();
        }
    });

	$('#{$this->getId()} .pastearea').pastableNonInputable();
	$('#{$this->getId()} .pastearea').on('pasteImage', function(ev, data){
        $('#{$this->getId()} .imagecarousel-upload').fileupload('add', {files: [data.blob]});
    });

    $('#{$this->getId()} .imagecarousel-upload').fileupload({
        url: '{$this->getAjaxUrl()}',
        dataType: 'json',
        autoUpload: true,
        {$this->buildJsUploadAcceptedFileTypesFilter()}
        maxFileSize: {$maxFileSizeInBytes},
        previewMaxHeight: $('#{$this->getId()} .imagecarousel-upload').height(),
        previewMaxWidth: $('#{$this->getId()}').width(),
        previewCrop: false,
        formData: {
            resource: '{$this->getPageId()}',
            element: '{$uploader->getInstantUploadButton()->getId()}',
            object: '{$widget->getMetaObject()->getId()}',
            action: '{$uploader->getInstantUploadAction()->getAliasWithNamespace()}'
        }
    })
    .on('fileuploadsend', function(e, data) {
        var oParams = data.formData;

        data.files.forEach(function(file){
            var fileReader = new FileReader();
            $jqSlickJs.slick('slickAdd', $({$this->buildJsSlideTemplate('""')}).append(file.preview)[0]);
            fileReader.onload = function () { 
                var sContent = {$this->buildJsFileContentEncoder($uploader->getFileContentAttribute()->getDataType(), 'fileReader.result', 'file.type')};
                {$this->buildJsBusyIconShow()}
                oParams.data = {
                    oId: '{$this->getMetaObject()->getId()}',
                    rows: [{
                        '{$uploader->getFilenameAttribute()->getAliasWithRelationPath()}': (file.name || 'Upload_' + {$this->getDateFormatter()->buildJsFormatDateObject('(new Date())', 'yyyyMMdd_HHmmss')} + '.png'),
                        '{$uploader->getFileContentAttribute()->getAliasWithRelationPath()}': sContent,
                        {$fileModificationColumnJs}
                    }]
                };

                {$this->buildJsLoadFilterHandleWidgetLinks('oParams.data')}                

                $.ajax({
                    url: "{$this->getAjaxUrl()}",
                    data: oParams,
                    method: 'POST',
                    success: function(json){
                        {$this->buildJsRefresh(true)}
            		},
            		error: function(jqXHR, textStatus,errorThrown){
                        {$this->buildJsBusyIconHide()}
                        {$this->buildJsShowErrorAjax('jqXHR')}
                        {$this->buildJsRefresh(true)}
            		}
            	});
            };
            fileReader.readAsBinaryString(file);
        });
        return false;
    });
JS;
        
        return $output;
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
        return $this->buildJsBusyIconHide() . "$('#{$this->getId()}').prepend('<div class=\"imagecarousel-loading\"><div class=\"datagrid-mask\" style=\"display:block\"></div><div class=\"datagrid-mask-msg\" style=\"display: block; left: 50%; height: 16px; margin-left: -107.555px; line-height: 16px;\"></div></div>');";
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::buildJsBusyIconHide()
     */
    public function buildJsBusyIconHide()
    {
        return "$('#{$this->getId()} .imagecarousel-loading').remove();";
    }
    
    /**
     * 
     * @see JsUploaderTrait::getUploader()
     */
    protected function getUploader() : Uploader
    {
        return $this->getWidget()->getUploader();
    }
    
    /**
     * 
     * @return JsDateFormatter
     */
    protected function getDateFormatter() : JsDateFormatter
    {
        return new JsDateFormatter(DataTypeFactory::createFromString($this->getWorkbench(), DateTimeDataType::class));
    }
}