<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JsonEditorTrait;
use exface\Core\Widgets\Tab;

/**
 * Renders JSON and UXON using the shared AJAX facade editor adapter.
 */
class EuiInputJson extends EuiInputText
{
    use JsonEditorTrait;

    /**
     * {@inheritDoc}
     * @see EuiInputText::init()
     */
    protected function init()
    {
        parent::init();
        // Make sure the scroll memory is activated every time the editor is shown by a tab.
        // Need this because jEasyUI tabs don't render their contents fully until it is shown.
        foreach($this->getWidget()->findParentsByClass(Tab::class) as $tab) {
            $tabsEl = $this->getFacade()->getElement($tab->getTabs());
            $tabsEl->addOnTabSelectScript($this->buildJsRememberScrollPos());
        }
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::getElementType()
     */
    public function getElementType() : ?string
    {
        return null;
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
    
    /**
     * {@inheritDoc}
     * @see EuiInputText::buildJs()
     */
    public function buildJs()
    {
        return $this->buildJsEventScripts() . $this->buildJsJsonEditor()
            . '$("#' . $this->getId() . '").parents(".exf-input").children("label").css("vertical-align", "top");';
    }

    /**
     * {@inheritDoc}
     * @see EuiInputText::buildHtmlHeadTags()
     */
    public function buildHtmlHeadTags()
    {
        return array_merge(parent::buildHtmlHeadTags(), $this->buildHtmlHeadTagsForJsonEditor());
    }

    /**
     * {@inheritDoc}
     * @see JsonEditorTrait::buildJsJsonEditorOnAjaxError()
     */
    protected function buildJsJsonEditorOnAjaxError() : string
    {
        return <<<JS
function(oRequest, domModal) {
    if (domModal) {
        // picoModal uses a separate stacking level from EasyUI windows.
        $.fn.window.defaults.zIndex = Math.max(
            $.fn.window.defaults.zIndex,
            parseInt(window.getComputedStyle(domModal).zIndex, 10) + 1
        );
    }
    {$this->buildJsShowErrorAjax('oRequest')}
}
JS;
    }
}