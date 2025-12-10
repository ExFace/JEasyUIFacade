<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Exceptions\Widgets\WidgetConfigurationError;
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
                $tabsEl->addOnTabSelectScript($this->buildJsMermaidInit(), $tab);
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
        return parent::buildCssElementClass() . ' exf-markdown markdown-body';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiValue::buildJs()
     */
    public function buildJs()
    {
        $js = parent::buildJs();
        
        $js .= $this->buildJsHyperlinksCatcher();
        
        if ($this->hasMermaidSupport()) {
            $js .= $this->buildJsMermaidInit();
        }
        
        return $js;
    }
    
    protected function buildJsMermaidInit() : string
    {
        return <<<JS

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

    /**
     * 
     * @return bool
     */
    protected function hasMermaidSupport() : bool
    {
        $widget = $this->getWidget();
        return ($widget instanceof Markdown) && $widget->hasRenderMermaidDiagrams();
    }

    /**
     * This function catches the hyperlink click event 
     * and overrides it if the "open_links_in" property is defined.
     * 
     * @return string
     */
    protected function buildJsHyperlinksCatcher() : string
    {
        $widget = $this->getWidget();
        $openLinksIn = $widget->getOpenLinksIn();
        
        if ($openLinksIn == null || $openLinksIn == 'self') return '';
            
            $openLinkInJs = '';
            
            switch ($openLinksIn) {
                case 'popup':
                    $openLinkInJs .= $this->buildJsOpenLinkInPopup('link.href', $widget->getOpenLinksInPopupWidth(), $widget->getOpenLinksInPopupHeight());
                    break;
                case 'new_tab':
                    $openLinkInJs .= $this->buildJsOpenLinkInNewTab('link.href');
                    break;
                default:
                    throw new WidgetConfigurationError($this->getWidget(), 'Invalid value: ' . $openLinksIn . '. Only self, popup or new_tab are supported!' );
            }
            
            return <<<JS
          
              (function(){
                const currentDiv = document.getElementById('{$this->getId()}');
                currentDiv.addEventListener('click', function (e) {
                  const link = e.target.closest('a[href]');
                  // If a hyperlink begins with a '#', it is an anchor link and should be executed normally.
                  if (!link 
                      || !currentDiv.contains(link)
                      || link.getAttribute('href').startsWith('#')
                  ) return;
                  
                  e.preventDefault();
                  {$openLinkInJs}
                });
              })();
JS;
    }

    /**
     * It creates JavaScript that opens a URL with a specified width and height in a simple pop-up window.
     * 
     * @param $url
     * @param $width
     * @param $height
     * @return string
     */
    protected function buildJsOpenLinkInPopup($url, $width, $height) : string
    {
        
        $widthTag = $width ? 'width=' . $width . ',' : '';
        $heightTag = $height ? 'height=' . $height . ',' : '';
        return <<<JS

        window.open({$url}, 'window', '{$widthTag} {$heightTag} toolbar=no, menubar=no, resizable=yes');
JS;
    }

    /**
     * It creates JavaScript that opens a URL in a new browser tab.
     * 
     * @param $url
     * @return string
     */
    protected function buildJsOpenLinkInNewTab($url) : string
    {
        return <<<JS
        
          window.open({$url}, '_blank');
JS;
    }
}