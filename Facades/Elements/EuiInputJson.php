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
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInputText::buildHtml()
     */
    public function buildHtml()
    {
        return $this->buildHtmlLabelWrapper($this->buildHtmlJsonEditor());
    }
    
    public function buildJs()
    {
        return $this->buildJsJsonEditor() . $this->buildJsAutosuggestFunction();
    }
}