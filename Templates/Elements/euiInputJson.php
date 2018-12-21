<?php
namespace exface\JEasyUiTemplate\Templates\Elements;

use exface\Core\Templates\AbstractAjaxTemplate\Elements\JsonEditorTrait;

class euiInputJson extends euiInputText
{
    use JsonEditorTrait;

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
    
    public function buildHtml()
    {
        $output = '<div id="' . $this->getId() . '" style="height: 100%; width: 100%;"></div>';
        return $this->buildHtmlLabelWrapper($output);
    }
    
    public function buildJs()
    {
        return $this->buildJsJsonEditor() . $this->buildJsAutosuggestFunction();
    }
}