<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\CommonLogic\Constants\Icons;
use exface\Core\CommonLogic\UxonObject;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JsUploaderTrait;
use exface\Core\Interfaces\Actions\ActionInterface;
use exface\Core\Widgets\DataButton;
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
            '<script type="text/javascript" src="vendor/npm-asset/jquery-comments/js/jquery-comments.js"></script>',
            '<link rel="stylesheet" type="text/css" href="vendor/npm-asset/jquery-comments/css/jquery-comments.css">'
        ];
    }

    public function buildHtml()
    {
        $chart_panel_options = "title: '{$this->getCaption()}'";
        $gridItemClass = $this->getMasonryItemClass() . ' exf-commentsfeed exf-data-widget';
        if ($this->getWidget()->getHideHeader()) {
            $gridItemClass .= ' exf-data-hide-header';
        }
        $panel = <<<HTML

        <div class="exf-grid-item {$gridItemClass}" style="width:{$this->getWidth()};min-width:{$this->getMinWidth()};padding:{$this->getPadding()};box-sizing:border-box;">
    
    	   {$this->buildHtmlComments()}
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

    {$this->buildJsButtons()}

JS;
    }

    protected function buildJsCommentsInit() : string
    {
        $btnCreateEl = $this->getFacade()->getElement($this->getWidget()->getButtonCreate());
        $btnCreateEl->addOnSuccessScript("$('#{$this->getId()}')[0].successCallback()");
        $btnCreateEl->addOnErrorScript("$('#{$this->getId()}')[0].errorCallback()");

        // Include system columns for update/delete
        $systemColsJs = '';
        foreach ($this->getWidget()->getColumns() as $col) {
            if ($col->isBoundToAttribute() && $col->getAttribute()->getRelationPath()->isEmpty() && $col->getAttribute()->isSystem()) {
                $systemColsJs .= "{$col->getDataColumnName()}: data.{$col->getDataColumnName()},";
            }
        } 
        $dateFormatJs = $this->escapeString($this->getWorkbench()->getCoreApp()->getTranslator()->translate('LOCALIZATION.DATE.DATETIME_FORMAT'), true, false);
        return <<<JS

            $(function() {
				var saveComment = function(data) {

					// Convert pings to human readable format -->
					// $(Object.keys(data.pings)).each(function(index, userId) {
					//     var fullname = data.pings[userId];
					//     var pingText = '@' + fullname;
					//     data.content = data.content.replace(new RegExp('@' + userId, 'g'), pingText);
					// });

					return data;
				}
				$('#{$this->getId()}').comments({
					profilePictureURL: 'https://viima-app.s3.amazonaws.com/media/public/defaults/user-icon.png',
					currentUserId: '{$this->getWorkbench()->getSecurity()->getAuthenticatedUser()->getUid()}',
					roundProfilePictures: true,
					textareaRows: 1,
					enableAttachments: false,
					enableHashtags: false,
					enablePinging: false,
                    enableUpvoting: false,
                    enableReplying: false,
                    enableEditing: true,
					scrollContainer: $(window),
                    fieldMappings: {
                                    id: '{$this->getWidget()->getCommentIdColumn()->getDataColumnName()}',
									created: '{$this->getWidget()->getCommentCreatedDateColumn()->getDataColumnName()}',
									modified: '{$this->getWidget()->getCommentEditedDateColumn()->getDataColumnName()}',
									content: '{$this->getWidget()->getCommentTitleColumn()->getDataColumnName()}',
									creator: '{$this->getWidget()->getCommentAuthorIdColumn()->getDataColumnName()}',
									fullname: '{$this->getWidget()->getCommentAuthorColumn()->getDataColumnName()}',
								    createdByCurrentUser: '{$this->getWidget()->getIsCurrentUserAuthor()->getDataColumnName()}',
									//parent: 'parent',
									//attachments: 'attachments',
									//pings: 'pings',
									//profilePictureURL: 'profile_picture_url',
									//isNew: 'is_new',
									//createdByAdmin: 'created_by_admin',
									//upvoteCount: 'upvote_count',
									//userHasUpvoted: 'user_has_upvoted'
								},
                    timeFormatter: function(time) {
                        console.log('datetime', time);
                        return exfTools.date.format(time, {$dateFormatJs})
                    },
					searchUsers: function(term, success, error) {
					    setTimeout(function() {
					        success(usersArray.filter(function(user) {
					            var containsSearchTerm = user.fullname.toLowerCase().indexOf(term.toLowerCase()) != -1;
					            var isNotSelf = user.id != 1;
					            return containsSearchTerm && isNotSelf;
					        }),
                            error());
					    }, 500);
					},
					getComments: function(success, error) {
                        var oParams={};
						{$this->buildJsDataLoadFunctionBody('oParams')};
					},
					postComment: function(data, success, error) {
                        var oDataRow = {
                            {$this->getWidget()->getCommentTitleColumn()->getDataColumnName()} : data.{$this->getWidget()->getCommentTitleColumn()->getDataColumnName()},
                        };
                        $('#{$this->getId()}').data('_exfInput', oDataRow);
                        $('#{$this->getId()}')[0].successCallback = function(){
                            success(data);
                        };
                        $('#{$this->getId()}')[0].errorCallback = error;
                        {$btnCreateEl->buildJsClickFunctionName()}();
					},
					putComment: function(data, success, error) {
						var oDataRow = {
                            {$systemColsJs}
                            {$this->getWidget()->getCommentTitleColumn()->getDataColumnName()} : data.{$this->getWidget()->getCommentTitleColumn()->getDataColumnName()},
                        };
                        $('#{$this->getId()}').data('_exfInput', oDataRow);
                        $('#{$this->getId()}')[0].successCallback = function(){
                            success(data);
                            // TODO Force to read all comments from the server!
                        };
                        $('#{$this->getId()}')[0].errorCallback = error;
                        {$btnCreateEl->buildJsClickFunctionName()}();
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
    public function buildJsDataLoadFunctionBody(string $oParamsJs = 'oParams', string $successJs = 'success', string $errorJs = 'error') : string
    {
        $widget = $this->getWidget();
        
        return <<<JS

    // Don't load if already loading    
        if ($('#{$this->getId()}').data('_loading')) return;	
        
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
            return;
        }

        param = $.extend(param, ({$oParamsJs} || {}));
        param.data.filters = {
            "operator": "AND",
            "ignore_empty_values": true,
            "conditions": [
                {
                    "expression": "OffenePunkte__Nummer",
                    "value": "OP-1530",
                    "comparator": "==",
                    "object_alias": "suedlink.Trasse.OffenePunkteKommentare",
                    "apply_to_aggregates": "true"
                }
            ],
            "nested_groups": []
        };
        console.log('AJAX request', param);
        
        $.ajax({
        url: "{$this->getAjaxUrl()}",
        data: param,
        method: 'POST',
        success: function(json){
                try {
                    console.log(json);

                    var carousel = $('#{$this->getId()}');

                    {$this->buildJsLoadFilterHandleWidgetLinks('json.rows')}
                        
                    {$successJs} (json.rows);
                    
                    $('#{$this->getId()}').data('_loading', 0);

                } catch (err) {
                    console.error(err);
                }
            },
            error: function(jqXHR, textStatus, errorThrown){
                $('#{$this->getId()}').data('_loading', 0);
                {$this->buildJsShowErrorAjax('jqXHR')}
                //TODO call error callback
                {$errorJs} (jqXHR, textStatus, errorThrown);
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
    
    /**
     * 
     * @see AbstractJqueryElement::buildJsDataGetter()
     */
    public function buildJsDataGetter(ActionInterface $action = null)
    {
        $widget = $this->getWidget();
        $dataObj = $this->getMetaObjectForDataGetter($action);
        // Determine the columns we need in the actions data
        $colNamesList = implode(',', $widget->getActionDataColumnNames());
        
        if ($action !== null && $action->isDefinedInWidget() && $action->getWidgetDefinedIn() instanceof DataButton) {
            $customMode = $action->getWidgetDefinedIn()->getInputRows();
        } else {
            $customMode = null;
        }
        
        switch (true) {
            // If no action or explicitly requested ALL rows, return all loaded data
            // TODO save the data loaded from the server somewhere and return it here
            case $customMode === DataButton::INPUT_ROWS_ALL:
            case $action === null:
                return "($('#{$this->getId()}').data('_exfData') || {oId: '{$widget->getMetaObject()->getId()}', rows: []})";
                
            // If the button requires none of the rows explicitly
            case $customMode === DataButton::INPUT_ROWS_NONE:
                return '{}';

            default:
                return <<<JS

                    (function(jqComments){
                        var oInputData = jqComments.data('_exfInput');
                        var oData = {
                            oId: "{$widget->getMetaObject()->getId()}",
                            rows: [
                                oInputData
                            ]
                        };
                        {$this->buildJsLoadFilterHandleWidgetLinks('oData')};  
                        console.log('data getter', oInputData);
                        return oData;
                    })($('#{$this->getId()}'))
JS;
        }
    }
}