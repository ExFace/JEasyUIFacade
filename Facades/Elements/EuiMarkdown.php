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
                            mermaid.initialize({
                                startOnLoad:true,
                                config: '#{$this->getId()} .language-mermaid',
                                theme: 'default'
                            });
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
            $includes[] = '<script type="text/javascript" src="' . $f->buildUrlToSource("LIBS.PANZOOM.JS") . '"></script>';
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
                    mermaid.initialize({
                        startOnLoad:true,
                        config: '#{$this->getId()} .language-mermaid',
                        theme: 'default'
                    });

                    mermaid.run({
                        querySelector: '.language-mermaid',
                        postRenderCallback: (id) => {

                            let svgChild = document.getElementById(id);  

                            if (svgChild) {

                                var sSvgId = svgChild.id;
                                var doPan = false;
                                var eventsHandler;
                                var panZoom;
                                var mousepos;

                                // Set the SVG height explicitly because otherwise panZoom will break it.
                                // see https://github.com/bumbu/svg-pan-zoom?tab=readme-ov-file#svg-height-is-broken
                                svgChild.setAttribute("height", svgChild.height.animVal.value + 'px');


                                // Only pan if clicked on an empty space. Click-drag on a node should select text.
                                // Idea from here: https://github.com/bumbu/svg-pan-zoom/issues/81
                                // TODO It does not seem to work though
                                
                                eventsHandler = {
                                    haltEventListeners: ['mousedown', 'mousemove', 'mouseup'], 
                                    mouseDownHandler: function (ev) {
                                        if (ev.target.id === sSvgId) {
                                            doPan = true;
                                            mousepos = { x: ev.clientX, y: ev.clientY };
                                        };
                                    }, 
                                    mouseMoveHandler: function (ev) {
                                        if (doPan) {
                                            panZoom.panBy({ x: ev.clientX - mousepos.x, y: ev.clientY - mousepos.y });
                                            mousepos = { x: ev.clientX, y: ev.clientY };
                                            window.getSelection().removeAllRanges();
                                        }
                                    },
                                    mouseUpHandler: function (ev) {
                                        doPan = false;
                                    }, 
                                    init: function (options) {
                                        options.svgElement.addEventListener('mousedown', this.mouseDownHandler, false);
                                        options.svgElement.addEventListener('mousemove', this.mouseMoveHandler, false);
                                        options.svgElement.addEventListener('mouseup', this.mouseUpHandler, false);
                                    }, 
                                    destroy: function (options) {
                                        options.svgElement.removeEventListener('mousedown', this.mouseDownHandler, false);
                                        options.svgElement.removeEventListener('mousemove', this.mouseMoveHandler, false);
                                        options.svgElement.removeEventListener('mouseup', this.mouseUpHandler, false);
                                    }
                                }

                                panZoom = svgPanZoom(
                                    '#' + sSvgId, {
                                    zoomEnabled: true
                                    , controlIconsEnabled: true
                                    , fit: 1
                                    , center: 1
                                    , customEventsHandler: eventsHandler
                                    , preventMouseEventsDefault: false
                                });

                            }
                        }
                    });
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