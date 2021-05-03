<?php
namespace exface\JEasyUIFacade\Facades\Elements;

class EuiInputMarkdown extends EuiInput
{

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::init()
     */
    protected function init()
    {
        parent::init();
        $this->setElementType('div');
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
        $contentJs = json_encode($this->getWidget()->getValueWithDefaults());
        
        if ($viewer) {
            $viewerOptions ='viewer: true,';
        } else {
            $viewerOptions = '';
        }
        
        return <<<JS
window.toastui.Editor.factory({
            el: document.querySelector('#{$this->getId()}'),
            height: 'calc(100% - 6px)',
            initialValue: $contentJs,
            initialEditType: 'wysiwyg',
            language: 'en',
            $viewerOptions
        });

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
        if ($this->getWidget()->isDisabled()) {
            
        } else {
            $editorInit = $this->buildJsMarkdownInitEditor();
        }
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
    public function buildJsValidator()
    {
        return $this->buildJsValidatorViaTrait();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJsEnabler()
     */
    public function buildJsEnabler()
    {
        // TODO
        return '$("#' . $this->getId() . '").removeAttr("disabled")';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJsDisabler()
     */
    public function buildJsDisabler()
    {
        // TODO
        return '$("#' . $this->getId() . '").attr("disabled", "disabled")';
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
        $includes[] = '<link rel="stylesheet" href="' . $f->buildUrlToSource('LIBS.CODEMIRROR.CSS') . '"/>';
        $includes[] = '<link rel="stylesheet" href="' . $f->buildUrlToSource('LIBS.TOASTUI.EDITOR_CSS') . '" />';
        $includes[] = '<script type="text/javascript" src="' . $f->buildUrlToSource("LIBS.CODEMIRROR.JS") . '"></script>';
        $includes[] = '<script type="text/javascript" src="' . $f->buildUrlToSource("LIBS.TOASTUI.EDITOR_JS") . '"></script>';
        $includes[] = '<script type="text/javascript" src="' . $f->buildUrlToSource("LIBS.TOASTUI.EDITOR_JS") . '"></script>';
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
}