<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\ToastUIEditorTrait;
use exface\Core\Widgets\InputMarkdown;

/**
 * JEasyUI implementation of the corresponding widget.
 * 
 * @see InputMarkdown
 */
class EuiInputMarkdown extends EuiInput
{
    use ToastUIEditorTrait;
    
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
        return $this->buildHtmlLabelWrapper($this->buildHtmlMarkdownEditor());
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
        $includes[] = '<link rel="stylesheet" href="' . $f->buildUrlToSource('LIBS.TOASTUI.EDITOR.CSS') . '" />';
        $includes[] = '<script type="text/javascript" src="' . $f->buildUrlToSource("LIBS.TOASTUI.EDITOR.JS") . '"></script>';
        //$includes[] = '<script type="text/javascript" src="' . $f->buildUrlToSource("LIBS.MERMAID.JS") . '"></script>';
        //$includes[] = '<script src="https://uicdn.toast.com/editor/latest/toastui-editor-all.min.js"></script>';
        return $includes;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJs()
     */
    public function buildJs()
    {
        $editorInit = $this->getWidget()->isDisabled() ? $this->buildJsMarkdownInitViewer() : $this->buildJsMarkdownInitEditor();
        return <<<JS

        var {$this->buildJsMarkdownVar()} = {$editorInit}
        {$this->buildJsLiveReference()}
        {$this->buildJsOnChangeHandler()}

JS;
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