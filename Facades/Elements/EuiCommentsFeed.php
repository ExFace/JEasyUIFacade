<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JsUploaderTrait;
use exface\Core\Widgets\Parts\Uploader;
use exface\Core\DataTypes\DateTimeDataType;
use exface\Core\Factories\DataTypeFactory;
use exface\Core\Facades\AbstractAjaxFacade\Formatters\JsDateFormatter;
use exface\JEasyUIFacade\Facades\Elements\Traits\EuiDataElementTrait;

/**
 * Uses jQuery Comments by Viima to implement the CommentsFeed widget
 * 
 * @link https://viima.github.io/jquery-comments/
 * 
 * @author Andrej Kabachnik
 * 
 * @method \exface\Core\Widgets\CommentsFeed getWidget() 
 */
class EuiCommentsFeed extends EuiData
{    
    use JsUploaderTrait;
    use EuiDataElementTrait;
    
    public function buildHtmlHeadTags()
    {
        $headers = array_merge(parent::buildHtmlHeadTags(), $this->buildHtmlHeadTagsForComments());
        
        return $headers;
    }

    protected function buildHtmlHeadTagsForComments() : array
    {
        return [
            '<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery.textcomplete/1.8.0/jquery.textcomplete.js"></script>',
            '<script type="text/javascript" src="vendor/npm-asset/jquery-comments/js/jquery-comments.min.js"></script>',
            '<link rel="stylesheet" type="text/css" href="vendor/npm-asset/jquery-comments/css/jquery-comments.css">'
        ];
    }

    public function buildHtml()
    {
        $chart_panel_options = "title: '{$this->getCaption()}'";
        //$this->addCarouselFeatureButtons($this->getWidget()->getToolbarMain()->getButtonGroupForSearchActions(), 1);
        $gridItemClass = $this->getMasonryItemClass() . ' exf-imagegallery exf-data-widget';
        if ($this->getWidget()->getHideHeader()) {
            $gridItemClass .= ' exf-data-hide-header';
        }
        $panel = <<<HTML

<div class="exf-grid-item {$gridItemClass}" style="width:{$this->getWidth()};min-width:{$this->getMinWidth()};padding:{$this->getPadding()};box-sizing:border-box;">
    <div class="easyui-panel" style="height: auto; width: 100%" id="{$this->getId()}_wrapper" data-options="{$chart_panel_options}, onResize: function(){ {$this->getOnResizeScript()} }">
    	{$this->buildHtmlTableHeader()}
        <div style="height:{$this->getHeight()}; width: 100%">
    	   {$this->buildHtmlComments()}
        </div>
    </div>
</div>

HTML;
    
        return $panel;
    }

    protected function buildHtmlComments() : string
    {
        return "<div id=\"{$this->getId()}\"></div>";
    }

    function buildJs()
    {
        return <<<JS

    {$this->buildJsForPanel()}

    function {$this->buildJsFunctionPrefix()}_init(){
        {$this->buildJsCommentsInit()}
    }
    
    {$this->buildJsDataLoadFunction()}

    {$this->buildJsFunctionPrefix()}_init();
    setTimeout(function(){
        {$this->buildJsDataLoadFunctionName()}();
    }, 0);

JS;
    }

    protected function buildJsCommentsInit() : string
    {
        return <<<JS

            $(function() {
				var saveComment = function(data) {

					<!-- // Convert pings to human readable format -->
					<!-- $(Object.keys(data.pings)).each(function(index, userId) { -->
					    <!-- var fullname = data.pings[userId]; -->
					    <!-- var pingText = '@' + fullname; -->
					    <!-- data.content = data.content.replace(new RegExp('@' + userId, 'g'), pingText); -->
					<!-- }); -->

					return data;
				}
				$('#{$this->getId()}').comments({
					profilePictureURL: 'https://viima-app.s3.amazonaws.com/media/public/defaults/user-icon.png',
					currentUserId: 1,
					roundProfilePictures: true,
					textareaRows: 1,
					enableAttachments: true,
					enableHashtags: false,
					enablePinging: false,
					scrollContainer: $(window),
					searchUsers: function(term, success, error) {
					    setTimeout(function() {
					        success(usersArray.filter(function(user) {
					            var containsSearchTerm = user.fullname.toLowerCase().indexOf(term.toLowerCase()) != -1;
					            var isNotSelf = user.id != 1;
					            return containsSearchTerm && isNotSelf;
					        }));
					    }, 500);
					},
					getComments: function(success, error) {
						setTimeout(function() {
							success(commentsArray);
						}, 500);
					},
					postComment: function(data, success, error) {
						setTimeout(function() {
							success(saveComment(data));
						}, 500);
					},
					putComment: function(data, success, error) {
						setTimeout(function() {
							success(saveComment(data));
						}, 500);
					},
					deleteComment: function(data, success, error) {
						setTimeout(function() {
							success();
						}, 500);
					},
					upvoteComment: function(data, success, error) {
						setTimeout(function() {
							success(data);
						}, 500);
					},
					validateAttachments: function(attachments, callback) {
						setTimeout(function() {
							callback(attachments);
						}, 500);
					},
				});
			});
JS;
    }
    
    protected function buildJsDataLoaderOnLoaded(string $dataJs) : string
    {
        return '';
    }

    /**
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiData::buildJsDataSource()
     */
    public function buildJsDataLoadFunctionBody(string $oParamsJs = 'oParams') : string
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

    param = $.extend(param, ({$oParamsJs} || {}));
	
	$.ajax({
       url: "{$this->getAjaxUrl()}",
       data: param,
       method: 'POST',
       success: function(json){
			try {
                var carousel = $('#{$this->getId()}');

                {$this->buildJsLoadFilterHandleWidgetLinks('json.rows')}
                    
                {$this->buildJsCommentsData('carousel', 'json')}
                
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

    protected function buildJsCommentsData() : string
    {
        // TODO
        return '';
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
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiData::buildJsResize()
     */
    protected function buildJsResize() : string
    {
        return $this->buildJsResizeInnerWidget();
    }
}