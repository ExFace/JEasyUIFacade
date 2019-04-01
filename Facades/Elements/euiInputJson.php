<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JsonEditorTrait;

class EuiInputJson extends EuiInputText
{
    use JsonEditorTrait;

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInputText::init()
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