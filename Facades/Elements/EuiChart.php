<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\Chart;
use exface\Core\Facades\AbstractAjaxFacade\Elements\EChartsTrait;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryToolbarsTrait;
use exface\JEasyUIFacade\Facades\Elements\Traits\EuiDataElementTrait;
use exface\Core\Interfaces\Widgets\iShowData;

/**
 * 
 * @method Chart getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class EuiChart extends EuiData
{
    use EChartsTrait, EuiDataElementTrait {
        EChartsTrait::buildJsDataLoadFunctionName insteadof EuiDataElementTrait;
        EChartsTrait::buildJsMessageOverlayShow insteadof EuiDataElementTrait;
        EChartsTrait::buildJsMessageOverlayHide insteadof EuiDataElementTrait;
        EChartsTrait::buildJsRowCompare as buildJsRowCompareViaEchartsTrait;
        EuiDataElementTrait::buildJsDataLoadFunctionBody as buildJsDataLoadFunctionBodyViaTrait;
    }

    protected function init()
    {
        parent::init();
        $widget = $this->getWidget();
        // Connect to an external data widget if a data link is specified for this chart
        $this->registerLiveReferenceAtLinkedElement();
        
        // Disable global buttons because jEasyUI charts do not have data getters yet
        $widget->getToolbarMain()->setIncludeGlobalActions(false);
        
        if ($widget->getHideHeader()){
            $this->addOnResizeScript("
                 var newHeight = $('#{$this->getId()}_wrapper > .panel').height();
                 $('#{$this->getId()}').height($('#{$this->getId()}').parent().height() - newHeight);
            ");
        }
    }
    
    /**
     * 
     * @see EuiDataElementTrait::getDataWidget()
     */
    protected function getDataWidget() : iShowData
    {
        return $this->getWidget()->getData();
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiData::buildHtml()
     */
    public function buildHtml() : string
    {
        $widget = $this->getWidget();
        $this->addChartButtons();
        
        // Create empty custom header if the chart does not have it's own controls and is bound to another data widget
        if ($widget->getDataWidgetLink()) {
            $customHeaderHtml = '';
        }
        
        $onResizeScript = <<<JS
        
setTimeout(function(){
    var chartDiv = $('#{$this->getId()}');
    chartDiv.height(chartDiv.parent().height() - chartDiv.prev().height());
    {$this->buildJsEChartsResize()};
}, 0);

JS;
        $this->addOnResizeScript($onResizeScript);
        
        return $this->buildHtmlPanelWrapper($this->buildHtmlChart(), $customHeaderHtml);
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiData::buildJs()
     */
    public function buildJs() : string
    {
        return <<<JS

                    {$this->buildJsForPanel()}

                    {$this->buildJsFunctions()}
                    
                    var {$this->buildJsEChartsVar()};
                    setTimeout(function(){
                        {$this->buildJsEChartsInit()}
                        {$this->buildJsEventHandlers()}
                        {$this->buildJsRefresh()}
                    });
JS;
    }
           
    /**
     * 
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\EChartTrait
     */
    public function buildJsEChartsInit(string $theme = null) : string
    {
        return <<<JS

    {$this->buildJsEChartsVar()} = echarts.init(document.getElementById('{$this->getId()}'), '{$theme}');
    
JS;
    }

    /**
     * Returns the JS code to fetch data: either via AJAX or from a Data widget (if the chart is bound to another data widget).
     *
     * @return string
     */
    protected function buildJsDataLoadFunctionBody() : string
    {        
        return ! $this->getWidget()->getDataWidgetLink() ? 'var oParams = {}; ' . $this->buildJsDataLoadFunctionBodyViaTrait() : '';
    }
    
    /**
     * 
     * @see EuiDataElementTrait::buildJsDataLoaderOnLoaded()
     */
    protected function buildJsDataLoaderOnLoaded(string $dataJs) : string
    {
        return $this->buildJsRedraw($dataJs . '.rows');
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
            if (($containerWidget = $widget->getParentByClass('exface\\Core\\Interfaces\\Widgets\\iContainOtherWidgets')) && ($containerWidget->countWidgetsVisible() > 1)) {
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
    public function buildHtmlHeadTags()
    {
        $widget = $this->getWidget();
        $dataIncludes = $widget->getDataWidgetLink() === null ? $this->getFacade()->getElement($this->getWidget()->getData())->buildHtmlHeadTags() : [];
        $includes = array_merge($dataIncludes, $this->buildHtmlHeadDefaultIncludes());
        
        // masonry for proper filter alignment
        $includes[] = '<script type="text/javascript" src="' . $this->getFacade()->buildUrlToSource('LIBS.MASONRY') . '"></script>';
        return $includes;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiData::buildJsRowCompare()
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\EChartsTrait::buildJsRowCompare()
     */
    protected function buildJsRowCompare(string $leftRowJs, string $rightRowJs, bool $trustUid = true) : string
    {
        return $this->buildJsRowCompareViaEChartsTrait($leftRowJs, $rightRowJs);
    }
}