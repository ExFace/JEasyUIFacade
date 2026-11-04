<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\CommonLogic\WidgetDimension;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JsUploaderTrait;
use exface\Core\Interfaces\Actions\ActionInterface;
use exface\Core\Widgets\DataButton;
use exface\Core\Widgets\Filter;
use exface\Core\Widgets\Parts\Uploader;
use exface\Core\DataTypes\DateTimeDataType;
use exface\Core\Factories\DataTypeFactory;
use exface\Core\Facades\AbstractAjaxFacade\Formatters\JsDateFormatter;
use exface\JEasyUIFacade\Facades\Elements\Traits\EuiDataElementTrait;

/**
 * Uses jQuery Comments by Viima to implement the CommentsFeed widget
 * 
 * The feed occupies one layout column unless a width is specified. Comments are saved
 * immediately through the widget's actions. A relation filter targeting the parent record
 * supplies the parent key for new comments; commenting stays disabled while that key is empty.
 * Attachments, replies, mentions and voting are not supported.
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
    use EuiDataElementTrait {
        init as protected initDataElement;
    }

    /**
     * Register internal actions before the configurator adds buttons to their toolbars.
     *
     * {@inheritDoc}
     * @see EuiData::init()
     */
    protected function init()
    {
        $this->getWidget()->getButtonCreate();
        $this->getWidget()->getButtonEdit();
        $this->getWidget()->getButtonDelete();
        $this->initDataElement();
    }
    
    /**
     * {@inheritDoc}
     * @see EuiData::buildHtmlHeadTags()
     */
    public function buildHtmlHeadTags()
    {
        $headers = array_merge(parent::buildHtmlHeadTags(), $this->buildHtmlHeadTagsForComments());
        
        return $headers;
    }

    /**
     * Load the local comments plugin, Markdown viewer and shared lifecycle adapter.
     *
     * @return string[]
     */
    protected function buildHtmlHeadTagsForComments() : array
    {
        $headers = [
            '<script type="text/javascript" src="vendor/bower-asset/jquery-comments/js/jquery-comments.js"></script>',
            '<link rel="stylesheet" type="text/css" href="vendor/bower-asset/jquery-comments/css/jquery-comments.css">'
        ];
        if ($this->getWidget()->getEnableMarkdown()) {
            $headers[] = '<script src="' . $this->getFacade()->buildUrlToSource('LIBS.MARKIAN.VIEWER.JS') . '"></script>';
            $headers[] = '<link rel="stylesheet" href="' . $this->getFacade()->buildUrlToSource('LIBS.MARKIAN.VIEWER.CSS') . '">';
            $headers[] = '<script src="' . $this->getFacade()->buildUrlToSource('LIBS.MARKIAN.BRIDGE.JS') . '"></script>';
        }
        return $headers;
    }

    /**
     * {@inheritDoc}
     * @see EuiData::buildHtml()
     */
    public function buildHtml()
    {
        $this->addElementCssClass('exf-commentsfeed' . ($this->getWidget()->hasCommentAvatarUrlColumn() ? '' : ' exf-commentsfeed-no-avatars'));
        $fitJs = $this->getHeight() === 'auto' ? 'false' : 'true';
        $classes = $this->buildCssElementClass() . ' ' . $this->getMasonryItemClass();
        if ($this->getWidget()->getHideHeader()) {
            $classes .= ' exf-data-hide-header';
        }
        $title = $this->escapeString($this->getCaption(), false, true);
        return <<<HTML

<div class="exf-grid-item exf-data-widget {$classes}" style="width:{$this->getWidth()};min-width:{$this->getMinWidth()};height:{$this->getHeight()};padding:{$this->getPadding()};box-sizing:border-box;">
    <div class="easyui-panel" id="{$this->getId()}_wrapper" style="height:auto" data-options="fit:{$fitJs}, width:'100%', title:'{$title}', onResize:function(){ {$this->getOnResizeScript()} }">
        {$this->buildHtmlTableHeader()}
        {$this->buildHtmlComments()}
    </div>
</div>
HTML;
    }

    /**
     * Use one layout column by default, while respecting an explicit width.
     *
     * {@inheritDoc}
     * @see EuiAbstractElement::getWidth()
     */
    public function getWidth(?WidgetDimension $width = null)
    {
        $width = $width ?? $this->getWidget()->getWidth();
        return parent::getWidth($width->isUndefined() ? new WidgetDimension($this->getWorkbench(), '1') : $width);
    }

    /**
     * Build the scrollable host for the comments plugin.
     */
    protected function buildHtmlComments() : string
    {
        return "<div id=\"{$this->getId()}\" style=\"overflow:auto\"></div>";
    }

    /**
     * {@inheritDoc}
     * @see EuiData::buildJs()
     */
    public function buildJs()
    {
        $this->registerCommentActionCallbacks();
        return <<<JS

    {$this->buildJsForPanel()}

    function {$this->buildJsFunctionPrefix()}_init(){
        {$this->buildJsCommentsInit()}
    }
    
    {$this->buildJsFunctionPrefix()}_init();

JS;
    }

    /**
     * Initialize field mappings and route comment operations through the hidden DataButtons.
     */
    protected function buildJsCommentsInit() : string
    {
        $btnCreateEl = $this->getFacade()->getElement($this->getWidget()->getButtonCreate());
        $btnEditEl = $this->getFacade()->getElement($this->getWidget()->getButtonEdit());
        $btnDeleteEl = $this->getFacade()->getElement($this->getWidget()->getButtonDelete());
        $avatarMappingJs = $this->getWidget()->hasCommentAvatarUrlColumn()
            ? 'profilePictureURL: ' . $this->escapeString($this->getWidget()->getCommentAvatarUrlColumn()->getDataColumnName()) . ','
            : '';
        
        // Include system columns for update/delete
        $systemColsJs = '';
        foreach ($this->getWidget()->getColumns() as $col) {
            if ($col->isBoundToAttribute() && $col->getAttribute()->getRelationPath()->isEmpty() && $col->getAttribute()->isSystem()) {
                $name = json_encode($col->getDataColumnName());
                $systemColsJs .= "oDataRow[{$name}] = oOriginal[{$name}];\n";
            }
        } 
        $dateFormatJs = $this->escapeString($this->getWorkbench()->getCoreApp()->getTranslator()->translate('LOCALIZATION.DATE.DATETIME_FORMAT'), true, false);
        $markdownOptionsJs = '';
        if ($this->getWidget()->getEnableMarkdown()) {
            $markdownOptionsJs = 'var oMarkdownOptions = ' . json_encode([
                'markdownCssUrls' => [$this->getFacade()->buildUrlToSource('LIBS.MARKIAN.MARKDOWN.CSS')],
                'markdownCssClasses' => $this->getFacade()->getConfig()->getOption('LIBS.MARKIAN.MARKDOWN.CSS_CLASSES')->toArray(),
                'softbreaks' => true
            ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) . ';';
        }
        return <<<JS

            $(function() {
                {$markdownOptionsJs}
				$('#{$this->getId()}').comments({
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
									modified: '_modified',
									content: '{$this->getWidget()->getCommentContentColumn()->getDataColumnName()}',
									creator: '{$this->getWidget()->getCommentAuthorIdColumn()->getDataColumnName()}',
									fullname: '{$this->getWidget()->getCommentAuthorColumn()->getDataColumnName()}',
								    createdByCurrentUser: '{$this->getWidget()->getCommentAuthorIsCurrentUserColumn()->getDataColumnName()}',
                                    dbModified: '{$this->getWidget()->getCommentEditedDateColumn()->getDataColumnName()}',
									//parent: 'parent',
									//attachments: 'attachments',
									//pings: 'pings',
                                    {$avatarMappingJs}
									//isNew: 'is_new',
									//createdByAdmin: 'created_by_admin',
									//upvoteCount: 'upvote_count',
									//userHasUpvoted: 'user_has_upvoted'
								},
                    timeFormatter: function(time) {
                        return exfTools.date.format(time, {$dateFormatJs})
                    },
                    refresh: function() {
                        $('#{$this->getId()} .actions > button.edit').each(function() {
                            var jqButton = $(this);
                            if (!jqButton.children('.l-btn').length) {
                                var sLabel = jqButton.html();
                                // Keep the plugin's native button and click handling around the template control.
                                $('<span>').appendTo(jqButton.empty()).linkbutton({
                                    text: sLabel,
                                    plain: true
                                });
                            }
                        });
                    },
					getComments: function(success, error) {
                        {$this->buildJsCommentsMarkdownInit()}
                        var oParams={};
						{$this->buildJsDataLoadFunctionBody('oParams')};
					},
					postComment: function(data, success, error) {
                        var oDataRow = {
                            {$this->getWidget()->getCommentContentColumn()->getDataColumnName()} : data.{$this->getWidget()->getCommentContentColumn()->getDataColumnName()},
                        };
                        $('#{$this->getId()}').data('_exfInput', oDataRow);
                        $('#{$this->getId()}').data('_exfError', error);
                        $('#{$this->getId()}').data('_exfBusyButton', $('#{$this->getId()} .commenting-field.main .send'));
                        {$btnCreateEl->buildJsClickFunctionName()}();
					},
					putComment: function(data, success, error) {
                        var oOriginal = ($('#{$this->getId()}').data('_exfData').rows || []).find(function(oRow) {
                            return oRow['{$this->getWidget()->getCommentIdColumn()->getDataColumnName()}'] === data['{$this->getWidget()->getCommentIdColumn()->getDataColumnName()}'];
                        });
						var oDataRow = {
                            '{$this->getWidget()->getCommentIdColumn()->getDataColumnName()}': data['{$this->getWidget()->getCommentIdColumn()->getDataColumnName()}'],
                            {$this->getWidget()->getCommentContentColumn()->getDataColumnName()} : data.{$this->getWidget()->getCommentContentColumn()->getDataColumnName()},
                        };
                        {$systemColsJs}
                        $('#{$this->getId()}').data('_exfInput', oDataRow);
                        $('#{$this->getId()}').data('_exfError', error);
                        $('#{$this->getId()}').data('_exfBusyButton', $('#{$this->getId()} .comment').filter(function() {
                            return $(this).attr('data-id') === String(data['{$this->getWidget()->getCommentIdColumn()->getDataColumnName()}']);
                        }).find('.update'));
                        {$btnEditEl->buildJsClickFunctionName()}();
					},
					deleteComment: function(data, success, error) {
                        var oOriginal = ($('#{$this->getId()}').data('_exfData').rows || []).find(function(oRow) {
                            return oRow['{$this->getWidget()->getCommentIdColumn()->getDataColumnName()}'] === data['{$this->getWidget()->getCommentIdColumn()->getDataColumnName()}'];
                        });
                        var oDataRow = {
                            '{$this->getWidget()->getCommentIdColumn()->getDataColumnName()}': data['{$this->getWidget()->getCommentIdColumn()->getDataColumnName()}']
                        };
                        {$systemColsJs}
                        $('#{$this->getId()}').data('_exfInput', oDataRow);
                        $('#{$this->getId()}').data('_exfError', error);
                        $('#{$this->getId()}').data('_exfBusyButton', $('#{$this->getId()} .comment').filter(function() {
                            return $(this).attr('data-id') === String(data['{$this->getWidget()->getCommentIdColumn()->getDataColumnName()}']);
                        }).find('.delete'));
                        // Reset while confirming; the action restores the spinner only after confirmation.
                        error();
                        {$btnDeleteEl->buildJsClickFunctionName()}();
					},
				});
                // Track the plugin's composer layout and use template buttons for its action controls.
                $('#{$this->getId()}')
                    .on('focusin.exfCommentsFeed focusout.exfCommentsFeed', '.actions > button.edit', function(oEvent) {
                        $(this).children('.l-btn').toggleClass('l-btn-focus', oEvent.type === 'focusin');
                    })
                    .on('click.exfCommentsFeed', '.commenting-field.main .textarea, .commenting-field.main .close', function() {
                        var jqField = $(this).closest('.commenting-field');
                        var bExpanded = jqField.find('.control-row').is(':visible');
                        if (jqField.hasClass('exf-commentsfeed-expanded') === bExpanded) {
                            return;
                        }
                        var domTextarea = jqField.find('.textarea')[0];
                        var oSelection = window.getSelection();
                        var oRange = bExpanded && document.activeElement === domTextarea
                            && oSelection && oSelection.rangeCount && domTextarea.contains(oSelection.anchorNode)
                            ? oSelection.getRangeAt(0).cloneRange() : null;
                        jqField.toggleClass('exf-commentsfeed-expanded', bExpanded);
                        // Keep the caret inside the input when hiding the avatar changes its bounds.
                        if (oRange !== null) {
                            oSelection.removeAllRanges();
                            oSelection.addRange(oRange);
                        } else if (bExpanded && document.activeElement === domTextarea && oSelection) {
                            oSelection.collapse(domTextarea, domTextarea.childNodes.length);
                        }
                    })
                    .on('focusin.exfCommentsFeed change.exfCommentsFeed', '.commenting-field', function() {
                        $(this).find('.control-row > .save, .control-row > .delete').each(function() {
                            var jqButton = $(this);
                            if (!jqButton.data('linkbutton')) {
                                var sLabel = jqButton.html();
                                // URL-less links avoid the plugin's link colors and span-specific button styles.
                                var jqLink = $('<a>', {
                                    role: 'button',
                                    'class': jqButton.attr('class')
                                });
                                jqButton.replaceWith(jqLink);
                                jqButton = jqLink.removeClass('highlight-background').linkbutton({
                                    text: sLabel,
                                    plain: jqLink.hasClass('delete')
                                });
                                // The plugin restores this markup after showing its inline spinner.
                                jqButton.data('original-content', jqButton.html());
                            }
                            var bEnabled = jqButton.hasClass('enabled');
                            jqButton.linkbutton(bEnabled ? 'enable' : 'disable').attr({
                                tabindex: 0,
                                'aria-disabled': String(!bEnabled)
                            });
                        });
                    })
                    .on('keydown.exfCommentsFeed', '.commenting-field .control-row > .save, .commenting-field .control-row > .delete', function(oEvent) {
                        if (oEvent.key !== 'Enter' && oEvent.key !== ' ') {
                            return;
                        }
                        oEvent.preventDefault();
                        oEvent.stopPropagation();
                        if ($(this).hasClass('enabled')) {
                            $(this).trigger('click');
                        }
                    });
			});
JS;
    }

    /**
     * Render displayed comments as Markdown while keeping the plugin's source editor unchanged.
     *
     * The plugin inserts the returned mount synchronously. The shared adapter disposes
     * viewers when sorting, refreshing or removing comments detaches their mounts.
     *
     * @return string
     */
    protected function buildJsCommentsMarkdownInit() : string
    {
        if (!$this->getWidget()->getEnableMarkdown()) {
            return '';
        }
        return <<<JS

                        var oComments = $('#{$this->getId()}').data('comments');
                        if (!oComments._exfMarkdownFormatter) {
                            var fnFormat = oComments.getFormattedCommentContent;
                            oComments.getFormattedCommentContent = function(oComment, bReplaceNewLines) {
                                if (bReplaceNewLines) {
                                    return fnFormat.call(this, oComment, bReplaceNewLines);
                                }
                                var jqContent = $('<div>').css('white-space', 'normal');
                                MarkianAdapter.mount(jqContent[0], Object.assign({}, oMarkdownOptions, {
                                    markdown: oComment.content
                                }), true);
                                return jqContent;
                            };
                            oComments._exfMarkdownFormatter = true;
                        }

JS;
    }
    
    /**
     * The plugin's getComments callback handles rendering instead of the generic loader.
     *
     * @see EuiDataElementTrait::buildJsDataLoaderOnLoaded()
     */
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
        $linkedFiltersValidJs = [];
        foreach ($this->getParentRelationFilters() as $filter) {
            $valueJs = $this->getFacade()->getElement($filter)->buildJsValueGetter();
            $linkedFiltersValidJs[] = "(function(mValue){ return mValue !== undefined && mValue !== null && mValue !== ''; })({$valueJs})";
        }
        $linkedFiltersValidJs = implode(' && ', $linkedFiltersValidJs) ?: 'true';
        $headersJs = empty($this->getAjaxHeaders()) ? '' : 'headers: ' . json_encode($this->getAjaxHeaders()) . ',';
        $saveParentHint = $this->getFacade()->getApp()->getTranslator()->translate('WIDGET.COMMENTSFEED.SAVE_PARENT');
        
        return <<<JS

        var jqComments = $('#{$this->getId()}');
        if (jqComments.data('_loading')) return;

        var param = {
            action: '{$widget->getLazyLoadingActionAlias()}',
            resource: "{$widget->getPage()->getAliasWithNamespace()}",
            element: "{$widget->getId()}",
            object: "{$widget->getMetaObject()->getId()}"
        };

        var checkOnBeforeLoad = function(param){
            {$this->buildJsOnBeforeLoadScript('param')}
            {$this->buildJsOnBeforeLoadAddConfiguratorData('param')}
            return {$linkedFiltersValidJs};
        }.call(jqComments[0], param);

        if (checkOnBeforeLoad === false) {
            jqComments.data('_exfData', {oId: '{$widget->getMetaObject()->getId()}', rows: []});
            {$successJs}([]);
            jqComments.find('.commenting-field.main').hide();
            if (!({$linkedFiltersValidJs})) {
                jqComments.find('.no-comments').text({$this->escapeString($saveParentHint)});
            } else {
                {$this->buildJsAutoloadDisabledMessageShow($widget->getEmptyTextIfInvalidFilters())}
            }
            jqComments.closest('.grid').masonry('layout');
            return;
        }

        param = $.extend(param, ({$oParamsJs} || {}));
        jqComments.data('_loading', true);
        
        return $.ajax({
            url: "{$this->getAjaxUrl()}",
            data: param,
            method: 'POST',
            dataType: 'json',
            {$headersJs}
            success: function(json){
                if (!Array.isArray(json.rows)) {
                    {$this->buildJsShowError('json.error || JSON.stringify(json)', '"Server error"')}
                    if (typeof {$errorJs} === 'function') { {$errorJs}(); }
                    return;
                }
                json.rows.forEach(function(oRow){
                    oRow._modified = oRow['{$widget->getCommentEditedDateColumn()->getDataColumnName()}'] === oRow['{$widget->getCommentCreatedDateColumn()->getDataColumnName()}']
                        ? null : oRow['{$widget->getCommentEditedDateColumn()->getDataColumnName()}'];
                });
                jqComments.data('_exfData', json);
                {$successJs}(json.rows);
                {$this->getOnLoadSuccess()}
                jqComments.closest('.grid').masonry('layout');
            },
            error: function(jqXHR, textStatus, errorThrown){
                {$this->buildJsShowErrorAjax('jqXHR')}
                if (typeof {$errorJs} === 'function') { {$errorJs}(jqXHR, textStatus, errorThrown); }
            },
            complete: function(){
                jqComments.data('_loading', false);
                if (jqComments.data('_exfRefreshPending')) {
                    jqComments.removeData('_exfRefreshPending');
                    {$this->buildJsRefresh()}
                }
            }
        });
	
JS;
    }

    /**
     * Register callbacks before generating the hidden action buttons.
     *
     * @return void
     */
    protected function registerCommentActionCallbacks() : void
    {
        foreach ([$this->getWidget()->getButtonCreate(), $this->getWidget()->getButtonEdit(), $this->getWidget()->getButtonDelete()] as $button) {
            $button->setRefreshInput(false);
            $element = $this->getFacade()->getElement($button);
            $element->addOnErrorScript("var fnError = $('#{$this->getId()}').data('_exfError'); if (fnError) { fnError(); }");
            $element->addOnSuccessScript($this->buildJsRefresh());
        }
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
     * Show the plugin's inline spinner on the active comment action.
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::buildJsBusyIconShow()
     */
    public function buildJsBusyIconShow()
    {
        return <<<JS
            (function(jqComments) {
                var jqButton = jqComments.data('_exfBusyButton');
                if (jqButton) {
                    jqComments.data('comments').setButtonState(jqButton, false, true);
                    var jqSpinner = jqButton.children().detach();
                    jqButton.html(jqButton.data('original-content')).find('.l-btn-text').empty().append(jqSpinner);
                    jqButton.linkbutton('disable').attr('aria-disabled', 'true');
                }
            })($('#{$this->getId()}'));
JS;
    }
    
    /**
     * Restore the comment action control without changing global progress UI.
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::buildJsBusyIconHide()
     */
    public function buildJsBusyIconHide()
    {
        return <<<JS
            (function(jqComments) {
                var jqButton = jqComments.data('_exfBusyButton');
                if (jqButton) {
                    jqComments.data('comments').setButtonState(jqButton, true, false);
                    jqButton.linkbutton('enable').attr('aria-disabled', 'false');
                    jqComments.removeData('_exfBusyButton');
                }
            })($('#{$this->getId()}'));
JS;
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
     * Let an auto-height feed grow with its comments instead of collapsing to a fitted panel.
     *
     * @see EuiDataElementTrait::buildJsResizeInnerWidget()
     */
    protected function buildJsResizeInnerWidget(string $elementId = null) : string
    {
        if ($this->getHeight() === 'auto') {
            return '';
        }

        return <<<JS
            $('#{$this->getId()}').height($('#{$this->getId()}_wrapper').innerHeight() - ($('#{$this->getIdOfToolbar()}').outerHeight() || 0));
JS;
    }

    /**
     * Reload authoritative rows after an action supplies potentially partial result data.
     *
     * {@inheritDoc}
     * @see EuiAbstractElement::buildJsDataSetter()
     */
    public function buildJsDataSetter(string $jsData) : string
    {
        return $this->buildJsRefresh();
    }

    /**
     * Find scalar relation filters that attach this feed's comments to its parent record.
     *
     * @return Filter[]
     */
    protected function getParentRelationFilters() : array
    {
        $parent = $this->getWidget()->getParent();
        $filters = [];
        if ($parent === null) {
            return $filters;
        }
        foreach ($this->getWidget()->getFilters() as $filter) {
            $attribute = $filter->getAttribute();
            if ($attribute !== null
                && $attribute->isRelation()
                && $attribute->getRelationPath()->isEmpty()
                && $attribute->getRelation()->getRightObject()->is($parent->getMetaObject())
                && in_array($filter->getComparator(), [null, '', '=', '=='], true)
                && ! $filter->hasCustomConditionGroup()
            ) {
                $filters[] = $filter;
            }
        }
        return $filters;
    }
    
    /**
     * 
     * @see AbstractJqueryElement::buildJsDataGetter()
     */
    public function buildJsDataGetter(ActionInterface $action = null)
    {
        $widget = $this->getWidget();
        $parentValuesJs = '';
        if ($action === $widget->getButtonCreate()->getAction()) {
            foreach ($this->getParentRelationFilters() as $filter) {
                $name = json_encode($filter->getAttributeAlias());
                $valueJs = $this->getFacade()->getElement($filter)->buildJsValueGetter();
                $parentValuesJs .= "oData.rows[0][{$name}] = {$valueJs};\n";
            }
        }
        
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
                        {$parentValuesJs}
                        {$this->buildJsLoadFilterHandleWidgetLinks('oData')};  
                        return oData;
                    })($('#{$this->getId()}'))
JS;
        }
    }

    /**
     * Reload comments without failing when the plugin has not been initialized yet.
     *
     * @return string
     */
    public function buildJsRefresh() : string
    {
        return <<<JS
            (function(jqComments){
                if (!jqComments.data('comments')) return;
                if (jqComments.data('_loading')) {
                    jqComments.data('_exfRefreshPending', true);
                } else {
                    jqComments.data('comments').fetchDataAndRender();
                }
            })($('#{$this->getId()}'));
JS;
    }
}