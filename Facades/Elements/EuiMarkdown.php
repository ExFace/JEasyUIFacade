<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\Markdown;

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
        $f = $this->getFacade();
        $includes[] = '<link href="' . $f->buildUrlToSource('LIBS.MARKDOWN.CSS') . '" rel="stylesheet">';
        if (($widget = $this->getWidget()) instanceof Markdown && $widget->hasRenderMermaidDiagrams()) {
            $includes[] = '<script type="text/javascript" src="' . $f->buildUrlToSource("LIBS.MERMAID.JS") . '"></script>';
        }
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
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiValue::buildJs()
     */
    public function buildJs()
    {
        $js = parent::buildJs();
        
        if ($this->getWidget()->hasRenderMermaidDiagrams()) {
            $js .= <<<JS

setTimeout(function(){
    mermaid.initialize({
        startOnLoad:true,
        theme: 'default'
    });
    mermaid.init(undefined, '.language-mermaid');
}, 0);

JS;
        }
        
        return $js;
    }
}