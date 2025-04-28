<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JsonEditorTrait;
use exface\Core\Widgets\Tab;

class EuiInputJson extends EuiInputText
{
    use JsonEditorTrait;

    protected function init()
    {
        parent::init();
        // Make sure the scroll memory is activated every time the editor is shown by a tab.
        // Need this because jEasyUI tabs don't render their contents fully until it is shown.
        foreach($this->getWidget()->findParentsByClass(Tab::class) as $tab) {
            $tabsEl = $this->getFacade()->getElement($tab->getTabs());
            $tabsEl->addOnTabSelectScript($this->buildJsRememberScrollPos($this->getId()));
        }
    }
    
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