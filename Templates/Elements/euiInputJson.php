<?php
namespace exface\JEasyUiTemplate\Templates\Elements;

class euiInputJson extends euiInputText
{

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiInputText::init()
     */
    protected function init()
    {
        parent::init();
        $this->setElementType('div');
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildCssHeightDefaultValue()
     */
    protected function buildCssHeightDefaultValue()
    {
        return ($this->getHeightRelativeUnit() * 5) . 'px';
    }

    public function buildHtml()
    {
        $output = '<div id="' . $this->getId() . '" style="height: 100%; width: 100%;"></div>';
        return $this->buildHtmlLabelWrapper($output);
    }

    public function buildJs()
    {
        return $this->buildJsJsonEditor();
    }
    
    protected function buildJsJsonEditor()
    {
        $init_value = $this->getValueWithDefaults() ? $this->getId() . '_JSONeditor.set(' . $this->getWidget()->getValue() . ');' : '';
        $script = <<<JS
            var {$this->getId()}_JSONeditor = new JSONEditor(document.getElementById("{$this->getId()}"), {
                            					mode: {$this->buildJsEditorModeDefault()},
                           						modes: {$this->buildJsEditorModes()}
                        					});
            {$init_value}
            {$this->getId()}_JSONeditor.expandAll();
            $('#{$this->getId()}').parents('.exf-input').children('label').css('vertical-align', 'top');
JS;
        return $script;
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsEditorModes() : string
    {
        if ($this->getWidget()->isDisabled()) {
            return "['view']";
        }
        return "['code', 'tree']";
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsEditorModeDefault() : string
    {
        if ($this->getWidget()->isDisabled()) {
            return "'view'";
        }
        return "'tree'";
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJsValueGetter()
     */
    public function buildJsValueGetter()
    {
        return 'function(){var text = ' . $this->getId() . '_JSONeditor.getText(); if (text === "{}" || text === "[]") { return ""; } else { return text;}}';
    }

    public function buildHtmlHeadTags()
    {
        $includes = parent::buildHtmlHeadTags();
        $includes[] = '<link href="exface/vendor/bower-asset/jsoneditor/dist/jsoneditor.min.css" rel="stylesheet">';
        $includes[] = '<script type="text/javascript" src="exface/vendor/bower-asset/jsoneditor/dist/jsoneditor.min.js"></script>';
        return $includes;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJsValidator()
     */
    function buildJsValidator()
    {
        return 'true';
    }
}