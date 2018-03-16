<?php
namespace exface\JEasyUiTemplate\Template\Elements;

use exface\Core\Widgets\ChartAxis;
use exface\Core\Widgets\ChartSeries;
use exface\Core\Widgets\Chart;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryFlotTrait;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryToolbarsTrait;

/**
 * 
 * @method Chart getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class euiChart extends euiData
{
    
    use JqueryFlotTrait;
    
    use JqueryToolbarsTrait {
        buildHtmlHeadTags as buildHtmlHeadTagsByTrait;
    }

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
        $configurator_element = $this->getTemplate()->getElement($widget->getConfiguratorWidget());
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
                 $('#{$this->getId()}').height($('#{$this->getId()}').parent().height()-newHeight);
            ");
        }
    }

    function buildHtml()
    {
        $output = '';
        $widget = $this->getWidget();
        
        // Create the header if the chart has it's own controls and is not bound to another data widget
        $header_html = '';
        if (! $widget->getDataWidgetLink()) {
            $header_html = $this->buildHtmlTableHeader();
            // Set the height of the canvas-div to auto. Otherwise the chart will be to high in some cases
            // (e.g. in vertical splits, where the chart has filters etc.)
            $canvas_height = 'auto';
        } else {
            // If the chart has no customizir, set the height to 100%. Auto will not work for some reason...
            $canvas_height = '100%';
        }
        
        $chart_panel_options = ", title: '{$this->getCaption()}'";
        
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
    	<div id="{$this->getId()}" style="height:{$canvas_height}; min-height: 100px; overflow: hidden;"></div>
    </div>
</div>
HTML;
        
        return $output;
    }

    public function buildJs()
    {
        /* @var $widget \exface\Core\Widgets\Chart */
        $widget = $this->getWidget();
        
        $output = '';
        
        // Add Scripts for the configurator widget first as they may be needed for the others
        $configurator_element = $this->getTemplate()->getElement($widget->getConfiguratorWidget());
        $output .= $configurator_element->buildJs();
        
        // Add scripts for the buttons
        $output .= $this->buildJsButtons();
        
        $output .= <<<JS
                    $('#{$configurator_element->getId()}').find('.grid').on( 'layoutComplete', function( event, items ) {
                        setTimeout(function(){
                            var newHeight = $('#{$this->getId()}_wrapper > .panel').height();
                            console.log(newHeight);
                            $('#{$this->getId()}').height($('#{$this->getId()}').parent().height()-newHeight);
                            console.log($('#{$this->getId()}').height());
                        }, 0);               
                    });
                    
JS;
        
        $output .= $this->buildJsPlotFunction();
        
        return $output;
    }

    /**
     * Returns the definition of the function elementId_load(urlParams) which is used to fethc the data via AJAX
     * if the chart is not bound to another data widget (in that case, the data should be provided by that widget).
     *
     * @return string
     */
    protected function buildJsAjaxLoaderFunction()
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
            if ($widget->getData()->getPaginate()) {
                $url_params .= '
                            , page: 1
                            , rows: ' . (! is_null($widget->getData()->getPaginatePageSize()) ? $widget->getData()->getPaginatePageSize() : $this->getTemplate()->getConfig()->getOption('WIDGET.CHART.PAGE_SIZE'));
            }
            
            // Loader function
            $output .= '
				function ' . $this->buildJsFunctionPrefix() . 'load(){
					' . $this->buildJsBusyIconShow() . '
					$.ajax({
						url: "' . $this->getAjaxUrl() . '",
                        method: "POST",
                        ' . $headers . '
                        data: {
                            ' . $url_params . '
                            , data: ' . $this->getTemplate()->getElement($widget->getConfiguratorWidget())->buildJsDataGetter() . '
                            
                        },
						success: function(data){
							' . $this->buildJsFunctionPrefix() . 'plot(data);
							' . $this->buildJsBusyIconHide() . '
						},
						error: function(jqXHR, textStatus, errorThrown){
							' . $this->buildJsShowError('jqXHR.responseText', 'jqXHR.status + " " + jqXHR.statusText') . '
							' . $this->buildJsBusyIconHide() . '
						}
					});
				}';
            
            // Call the data loader to populate the Chart initially
            $output .= $this->buildJsRefresh();
        }
        
        return $output;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\JEasyUiTemplate\Template\Elements\euiAbstractElement::getHeight()
     */
    function getHeight()
    {
        // Die Hoehe des Charts passt sich nicht automatisch dem Inhalt an. Wenn er also
        // nicht den gesamten Container ausfuellt, kollabiert er vollstaendig. Deshalb
        // wird hier die Hoehe des Charts gesetzt, wenn sie nicht definiert ist, und
        // er nicht alleine im Container ist.
        $widget = $this->getWidget();
        
        if ($widget->getHeight()->isUndefined()) {
            if (($containerWidget = $widget->getParentByType('exface\\Core\\Interfaces\\Widgets\\iContainOtherWidgets')) && ($containerWidget->countWidgetsVisible() > 1)) {
                $widget->setHeight($this->getTemplate()->getConfig()->getOption('WIDGET.CHART.HEIGHT_DEFAULT'));
            }
        }
        return parent::getHeight();
    }
    
    public function buildJsBusyIconShow()
    {
        return "$('#{$this->getId()}_wrapper').prepend('<div class=\"panel-loading\" style=\"height: 15px;\"></div>');";
    }
    
    public function buildJsBusyIconHide()
    {
        return "$('#{$this->getId()}_wrapper .panel-loading').remove();";
    }
    
    public function buildHtmlHeadTags()
    {
        $includes = array_merge(parent::buildHtmlHeadTags(), $this->buildHtmlHeadTagsByTrait());
        
        $includes[] = '<script type="text/javascript" src="exface/vendor/exface/JEasyUiTemplate/Template/js/flot/plugins/axislabels/jquery.flot.axislabels.js"></script>';
        $includes[] = '<script type="text/javascript" src="exface/vendor/exface/JEasyUiTemplate/Template/js/flot/plugins/jquery.flot.orderBars.js"></script>';
        // masonry for proper filter alignment
        $includes[] = '<script type="text/javascript" src="exface/vendor/bower-asset/masonry/dist/masonry.pkgd.min.js"></script>';
        return $includes;
    }
}
?>