<?php
namespace exface\JEasyUiTemplate\Templates\Elements;

class euiMarkdown extends euiHtml
{    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildHtmlHeadTags()
     */
    public function buildHtmlHeadTags()
    {
        $includes = parent::buildHtmlHeadTags();   
        $includes[] = '<link href="' . $this->getTemplate()->buildUrlToSource('LIBS.MARKDOWN.CSS') . '" rel="stylesheet">';
        return $includes;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiValue::buildCssElementClass()
     */
    public function buildCssElementClass()
    {
        return parent::buildCssElementClass() . ' markdown-body';
    }
}
?>