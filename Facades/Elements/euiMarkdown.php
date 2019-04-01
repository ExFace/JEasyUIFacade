<?php
namespace exface\JEasyUIFacade\Facades\Elements;

class EuiMarkdown extends EuiHtml
{    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildHtmlHeadTags()
     */
    public function buildHtmlHeadTags()
    {
        $includes = parent::buildHtmlHeadTags();   
        $includes[] = '<link href="' . $this->getFacade()->buildUrlToSource('LIBS.MARKDOWN.CSS') . '" rel="stylesheet">';
        return $includes;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiValue::buildCssElementClass()
     */
    public function buildCssElementClass()
    {
        return parent::buildCssElementClass() . ' markdown-body';
    }
}
?>