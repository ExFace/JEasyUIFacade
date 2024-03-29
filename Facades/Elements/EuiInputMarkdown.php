<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\InputMarkdown;

class EuiInputMarkdown extends EuiInput
{
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::getElementType()
     */
    public function getElementType() : ?string
    {
        return 'div';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildCssHeightDefaultValue()
     */
    protected function buildCssHeightDefaultValue()
    {
        return ($this->getHeightRelativeUnit() * 4) . 'px';
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildHtml()
     */
    public function buildHtml()
    {
        $editor = $this->buildHtmlMarkdownEditor('markdown-editor');
        return $this->buildHtmlLabelWrapper($editor);
    }
    
    /**
     * 
     * @param string $cssClass
     * @return string
     */
    protected function buildHtmlMarkdownEditor(string $cssClass = '') : string
    {
        return <<<HTML

                <div id="{$this->getId()}" class="{$cssClass}"></div>  
HTML;
    }
    
    /**
     * 
     * @param bool $viewer
     * @return string
     */
    protected function buildJsMarkdownInitEditor(bool $viewer = false) : string
    {
        $widget = $this->getWidget();
        $contentJs = $this->escapeString($widget->getValueWithDefaults(), true, false);
        
        $viewerOptions = '';
        if ($viewer === true) {
            $viewerOptions .= 'viewer: true,';
        }
        
        $editorOptions = "
                initialEditType: '" . ($widget->getEditorMode() === InputMarkdown::MODE_WYSIWYG ? 'wysiwyg' : 'markdown') . "',
";
        
        
        
        return <<<JS
function(){
            
            var ed = new toastui.Editor({
                el: document.querySelector('#{$this->getId()}'),
                height: '100%',
                initialValue: ($contentJs || ''),
                language: 'en',
                autofocus: false,
                $editorOptions
                $viewerOptions
                events: {
                    beforePreviewRender: function(sHtml){
                        setTimeout(function(){
                            var oEditor = {$this->buildJsMarkdownVar()};
                            oEditor.refreshMermaid();
                        }, 0);
                    }
                }
            });

            ed.insertToolbarItem(
                { 
                    groupIndex: 0, 
                    itemIndex: 0 
                }, {
                    name: 'Full screen',
                    tooltip: 'Full screen',
                    el: $('<button type="button" style="margin: -7px -5px; background: transparent;" onclick="{$this->buildJsMarkdownVar()}.toggleFullScreen(this)"><i class="fa fa-expand" style="padding: 4px;border: 1px solid black;margin-top: 1px"></i></button>')[0]
                }
            );

            ed.toggleFullScreen = function(domBtn){
                var jqWrapper = $('#{$this->getId()}');
                var oEditor = {$this->buildJsMarkdownVar()};
                var jqBtn = $(domBtn);
                var bExpanding = ! jqWrapper.hasClass('fullscreen');

                jqWrapper.toggleClass('fullscreen'); 
                jqBtn.find('i')
                    .removeClass('fa-expand')
                    .removeClass('fa-compress')
                    .addClass(bExpanding ? 'fa-compress' : 'fa-expand');
                if (bExpanding && jqWrapper.innerWidth() > 800) {
                    oEditor.changePreviewStyle('vertical');
                    oEditor.refreshMermaid();
                } else {
                    oEditor.changePreviewStyle('tab');
                }
            }

            mermaid.initialize({
                startOnLoad:true,
                theme: 'default'
            });
            ed.refreshMermaid = function() {
                mermaid.init(undefined, '.toastui-editor-md-preview code[data-language="mermaid"]');
            }

            return ed;
}();
JS;
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsMarkdownVar() : string
    {
        return "{$this->buildJsFunctionPrefix()}_editor";
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsMarkdownRemove() : string
    {
        return "{$this->buildJsMarkdownVar()}.remove();";
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJs()
     */
    public function buildJs()
    {
        $editorInit = $this->buildJsMarkdownInitEditor($this->getWidget()->isDisabled());
        return <<<JS

        var {$this->buildJsMarkdownVar()} = {$editorInit}
        {$this->buildJsLiveReference()}
        {$this->buildJsOnChangeHandler()}

JS;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJsValueSetterMethod()
     */
    public function buildJsValueSetter($value)
    {
        return "{$this->buildJsMarkdownVar()}.setMarkdown({$value})";
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsValueGetter()
     */
    public function buildJsValueGetter()
    {
        return "{$this->buildJsMarkdownVar()}.getMarkdown()";
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJsValidator()
     */
    public function buildJsValidator(?string $valJs = null) : string
    {
        return $this->buildJsValidatorViaTrait($valJs);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJsSetDisabled()
     */
    public function buildJsSetDisabled(bool $trueOrFalse) : string
    {
        // TODO
        if ($trueOrFalse === true) {
            return '$("#' . $this->getId() . '").attr("disabled", "disabled")';
        } else {
            return '$("#' . $this->getId() . '").removeAttr("disabled")';
        }
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildHtmlHeadTags()
     */
    public function buildHtmlHeadTags()
    {
        $f = $this->getFacade();
        $includes = parent::buildHtmlHeadTags();
        $includes[] = '<link rel="stylesheet" href="' . $f->buildUrlToSource('LIBS.TOASTUI.EDITOR_CSS') . '" />';
        $includes[] = '<script type="text/javascript" src="' . $f->buildUrlToSource("LIBS.TOASTUI.EDITOR_JS") . '"></script>';
        $includes[] = '<script type="text/javascript" src="' . $f->buildUrlToSource("LIBS.MERMAID.JS") . '"></script>';
        //$includes[] = '<script src="https://uicdn.toast.com/editor/latest/toastui-editor-all.min.js"></script>';
        return $includes;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildCssElementClass()
     */
    public function buildCssElementClass()
    {
        return parent::buildCssElementClass() . ' exf-input-markdown';
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJsRequiredGetter()
     */
    protected function buildJsRequiredGetter() : string
    {
        return $this->getWidget()->isRequired() ? "true" : "false";
    }
}