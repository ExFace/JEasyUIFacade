<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\Markdown;
use exface\Core\Widgets\Tab;

class EuiMarkdown extends EuiHtml
{    
    protected function init()
    {
        parent::init();

        // Mermaid diagrams throw JS exceptions if renderend in an inivisble element. This
        // breaks diagrams in tabs, which are very common in debug widgets. Thus, we need
        // to treat tabs separately here and make sure any tab switch leads to an attempt
        // to render the then-visible diagrams.
        if ($this->hasMermaidSupport()) {
            foreach($this->getWidget()->findParentsByClass(Tab::class) as $tab) {
                $tabsEl = $this->getFacade()->getElement($tab->getTabs());
                $tabsEl->addOnTabSelectScript(<<<JS

                    (function(){
                        var jqMD = $('#{$this->getId()}');
                        if (jqMD.is(':visible')) {
                            mermaid.init(undefined, '#{$this->getId()} .language-mermaid');
                        }
                    })()
JS
                    , $tab
                );
            }
        }
    }
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
        if ($this->hasMermaidSupport()) {
            $includes[] = '<script type="text/javascript" src="' . $f->buildUrlToSource("LIBS.MERMAID.JS") . '"></script>';
            $includes[] = <<<JS

    <script language="javascript">
        mermaid.initialize({
            startOnLoad:true,
            theme: 'default'
        });
    </script>
JS;
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
        
        if ($this->hasMermaidSupport()) {
            $js .= <<<JS

setTimeout(function(){
    var jqMD = $('#{$this->getId()}');
    if (jqMD.is(':visible')) {
        mermaid.init(undefined, '#{$this->getId()} .language-mermaid');
    }
}, 0);

JS;
        }
        
        return $js;
    }

    /**
     * 
     * @return bool
     */
    protected function hasMermaidSupport() : bool
    {
        $widget = $this->getWidget();
        return ($widget instanceof Markdown) && $widget->hasRenderMermaidDiagrams();
    }
}