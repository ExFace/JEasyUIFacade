<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JsonEditorTrait;

class EuiInputJson extends EuiInputText
{
    use JsonEditorTrait;
    
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