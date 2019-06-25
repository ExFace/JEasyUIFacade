<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\Chart;
use exface\Core\Facades\AbstractAjaxFacade\Elements\EChartsTrait;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryToolbarsTrait;

/**
 * 
 * @method Chart getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class EuiChart extends EuiData
{
    
    use EChartsTrait;
    
    use JqueryToolbarsTrait;

    private $on_change_script = '';

    protected function init()
    {
        parent::init();
        $widget = $this->getWidget();
        // Connect to an external data widget if a data link is specified for this chart
        $this->registerLiveReferenceAtLinkedElement();
        
        // Disable global buttons because jEasyUI charts do not have data getters yet
        $widget->getToolbarMain()->setIncludeGlobalActions(false);
        
        // Make the configurator resize together with the chart layout.
        $configurator_element = $this->getFacade()->getElement($widget->getConfiguratorWidget());
        // FIXME how to make the configurator resize when the chart is resized?
        $this->addOnResizeScript("
            /*if(typeof $('#" . $configurator_element->getId() . "')." . $configurator_element->getElementType() . "() !== 'undefined') {
                setTimeout(function(){
                    $('#" . $configurator_element->getId() . "')." . $configurator_element->getElementType() . "('resize');
                }, 0);     
            }*/
        ");
        
        if ($widget->getHideHeader()){
            $this->addOnResizeScript("
                 var newHeight = $('#{$this->getId()}_wrapper > .panel').height();
                 $('#{$this->getId()}').height($('#{$this->getId()}').parent().height() - newHeight);
            ");
        }
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiData::buildHtml()
     */
    public function buildHtml() : string
    {
        $output = '';
        $widget = $this->getWidget();
        
        // Create the header if the chart has it's own controls and is not bound to another data widget
        $header_html = '';
        if (! $widget->getDataWidgetLink()) {
            $header_html = $this->buildHtmlTableHeader();
        }
        
        $chart_panel_options = ", title: '{$this->getCaption()}'";
        
        $onResizeScript = <<<JS

setTimeout(function(){
    var chartDiv = $('#{$this->getId()}');
    chartDiv.height(chartDiv.parent().height() - chartDiv.prev().height());
    {$this->buildJsEChartsResize()};
}, 0);

JS;
        $this->addOnResizeScript($onResizeScript);
        
        // Create the panel for the chart
        // overflow: hidden loest ein Problem im JavaFX WebView-Browser, bei dem immer wieder
        // Scrollbars ein- und wieder ausgeblendet wurden. Es trat in Verbindung mit Masonry
        // auf, wenn mehrere Elemente auf einer Seite angezeigt wurden (u.a. ein Chart) und
        // das Layout umgebrochen hat. Da sich die Groesse des Charts sowieso an den Container
        // anpasst sollte overflow: hidden keine weiteren Auswirkungen haben.
        $output = <<<HTML

<div class="exf-grid-item {$this->getMasonryItemClass()}" style="width:{$this->getWidth()};min-width:{$this->getMinWidth()};height:{$this->getHeight()};padding:{$this->getPadding()};box-sizing:border-box;">
    <div class="easyui-panel" style="height: auto;" id="{$this->getId()}_wrapper" data-options="fit: true {$chart_panel_options}, onResize: function(){ {$this->getOnResizeScript()} }">
    	{$header_html}
    	{$this->buildHtmlChart()}
    </div>
</div>

HTML;
        
        return $output;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiData::buildJs()
     */
    public function buildJs() : string
    {
        /* @var $widget \exface\Core\Widgets\Chart */
        $widget = $this->getWidget();
        
        $output = '';
        
        // Add Scripts for the configurator widget first as they may be needed for the others
        $configurator_element = $this->getFacade()->getElement($widget->getConfiguratorWidget());
        $output .= $configurator_element->buildJs();
        
        // Add scripts for the buttons
        $output .= $this->buildJsButtons();
        
        $output .= <<<JS

                    $('#{$configurator_element->getId()}').find('.grid').on( 'layoutComplete', function( event, items ) {
                        setTimeout(function(){
                            var newHeight = $('#{$this->getId()}_wrapper > .panel').height();
                            $('#{$this->getId()}').height($('#{$this->getId()}').parent().height()-newHeight);
                        }, 0);               
                    });
                    
JS;
        
        $output .= $this->buildJsEChartsInit();
        $output .= $this->buildJsFunctions();
        $output .= $this->buildJsEventHandlers();
        $output .= $this->buildJsRefresh();
        
        return $output;
    }

    /**
     * Returns the JS code to fetch data: either via AJAX or from a Data widget (if the chart is bound to another data widget).
     *
     * @return string
     */
    protected function buildJsDataLoadFunctionBody() : string
    {
        $widget = $this->getWidget();
        $output = '';
        if (! $widget->getDataWidgetLink()) {
            
            $headers = ! empty($this->getAjaxHeaders()) ? 'headers: ' . json_encode($this->getAjaxHeaders()) . ',' : '';
            
            $url_params = '
                            resource: "' . $widget->getPage()->getAliasWithNamespace() . '"
                            , element: "' . $widget->getData()->getId(). '"
                            , object: "' . $widget->getMetaObject()->getId(). '"
                            , action: "' . $widget->getLazyLoadingActionAlias(). '"
            ';
            
            // send sort information
            if (count($widget->getData()->getSorters()) > 0) {
                foreach ($widget->getData()->getSorters() as $sorter) {
                    $sort .= ',' . urlencode($sorter->getProperty('attribute_alias'));
                    $order .= ',' . urldecode($sorter->getProperty('direction'));
                }
                $url_params .= '
                            , sort: "' . substr($sort, 1) . '"
                            , order: "' . substr($order, 1) . '"';
            }
            
            // send pagination/limit information. Charts currently do not support real pagination, but just a TOP-X display.
            if ($widget->getData()->isPaged()) {
                $url_params .= '
                            , page: 1
                            , rows: ' . $widget->getData()->getPaginator()->getPageSize($this->getFacade()->getConfig()->getOption('WIDGET.CHART.PAGE_SIZE'));
            }
            
            // Loader function
            $output .= '
					' . $this->buildJsBusyIconShow() . '
					$.ajax({
						url: "' . $this->getAjaxUrl() . '",
                        method: "POST",
                        ' . $headers . '
                        data: {
                            ' . $url_params . '
                            , data: ' . $this->getFacade()->getElement($widget->getConfiguratorWidget())->buildJsDataGetter() . '
                            
                        },
						success: function(data){
							' . $this->buildJsRedraw('data.rows') . '
							' . $this->buildJsBusyIconHide() . '
						},
						error: function(jqXHR, textStatus, errorThrown){
							' . $this->buildJsShowError('jqXHR.responseText', 'jqXHR.status + " " + jqXHR.statusText') . '
							' . $this->buildJsBusyIconHide() . '
						}
					});
				';
        }
        
        return $output;
    }

    /**
     *
     * {@inheritdoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::getHeight()
     */
    public function getHeight() : string
    {
        // Die Hoehe des Charts passt sich nicht automatisch dem Inhalt an. Wenn er also
        // nicht den gesamten Container ausfuellt, kollabiert er vollstaendig. Deshalb
        // wird hier die Hoehe des Charts gesetzt, wenn sie nicht definiert ist, und
        // er nicht alleine im Container ist.
        $widget = $this->getWidget();
        
        if ($widget->getHeight()->isUndefined()) {
            if (($containerWidget = $widget->getParentByType('exface\\Core\\Interfaces\\Widgets\\iContainOtherWidgets')) && ($containerWidget->countWidgetsVisible() > 1)) {
                $widget->setHeight($this->getFacade()->getConfig()->getOption('WIDGET.CHART.HEIGHT_DEFAULT'));
            }
        }
        return parent::getHeight();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::buildJsBusyIconShow()
     */
    public function buildJsBusyIconShow() : string
    {
        return $this->buildJsEChartsShowLoading();
    }
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::buildJsBusyIconHide()
     */
    public function buildJsBusyIconHide() : string
    {
        return $this->buildJsEChartsHideLoading();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildHtmlHeadTags()
     */
    public function buildHtmlHeadTags() : array
    {
        $widget = $this->getWidget();
        $dataIncludes = $widget->getDataWidgetLink() === null ? $this->getFacade()->getElement($this->getWidget()->getData())->buildHtmlHeadTags() : [];
        $includes = array_merge($dataIncludes, $this->buildHtmlHeadDefaultIncludes());
        
        // masonry for proper filter alignment
        $includes[] = '<script type="text/javascript" src="' . $this->getFacade()->buildUrlToSource('LIBS.MASONRY') . '"></script>';
        return $includes;
    }
}