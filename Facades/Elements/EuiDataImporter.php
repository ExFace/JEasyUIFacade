<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JExcelTrait;
use exface\JEasyUIFacade\Facades\Elements\Traits\EuiPanelWrapperTrait;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryToolbarsTrait;
use exface\Core\Interfaces\Widgets\iContainOtherWidgets;

class EuiDataImporter extends EuiAbstractElement
{
    use JExcelTrait;
    use JqueryToolbarsTrait;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::buildHtml()
     */
    public function buildHtml()
    {
        $toolbar = $this->buildHtmlToolbars();
        $widget = $this->getWidget();
        
        $style = '';
        
        if ($widget->hasParent() && ($parent = $widget->getParent()) instanceof iContainOtherWidgets) {
            if ($parent->isFilledBySingleWidget()) {
                $style .= 'height: 100%';
            }
        }
        
        return <<<HTML
        <div class="{$this->buildCssElementClass()}" style="$style">
            <div class="datatable-toolbar datagrid-toolbar" style="height: 36px">
                {$toolbar}
            </div>
            {$this->buildHtmlJExcel()}
        </div>

HTML;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::buildJs()
     */
    public function buildJs()
    {
        // If there is a preview-button, we need to call the data setter with it's
        // result after it's action was called successfully. 
        if ($this->getWidget()->hasPreview() === true) {
            $this->getFacade()->getElement($this->getWidget()->getPreviewButton())->addOnSuccessScript($this->buildJsDataSetter('response'));
        }
        return <<<JS
        setTimeout(function(){
            {$this->buildJsJExcelInit()}
        }, 0);
        {$this->buildJsToolbars()}
        {$this->buildJsFunctionsForJExcel()}

JS;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildHtmlHeadTags()
     */
    public function buildHtmlHeadTags()
    {
        $includes = array_merge(
            parent::buildHtmlHeadTags(),
            $this->buildHtmlHeadTagsForJExcel()
            );
        
        array_unshift($includes, '<script type="text/javascript">' . $this->buildJsFixJqueryImportUseStrict() . '</script>');
        
        return $includes;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildCssElementClass()
     */
    public function buildCssElementClass()
    {
        return 'exf-dataimporter';
    }
}