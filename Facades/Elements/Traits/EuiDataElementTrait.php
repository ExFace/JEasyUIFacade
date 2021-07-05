<?php
namespace exface\JEasyUIFacade\Facades\Elements\Traits;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryToolbarsTrait;
use exface\Core\Interfaces\Widgets\iShowData;

/**
 * 
 * @author Andrej Kabachnik
 *
 */
trait EuiDataElementTrait
{
    use JqueryToolbarsTrait;

    abstract protected function buildJsDataLoaderOnLoaded(string $dataJs) : string;
    
    protected function getDataWidget() : iShowData
    {
        return $this->getWidget();
    }
    
    protected function init()
    {
        parent::init();
        $widget = $this->getWidget();
        
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
    protected function buildHtmlPanelWrapper(string $contentHtml, string $customHeaderHtml = null) : string
    {
        $output = '';
        
        $header_html = $customHeaderHtml ?? $this->buildHtmlTableHeader();
        
        $panel_options = ", title: '{$this->getCaption()}'";
        
        // Create the panel for the data widget
        // overflow: hidden loest ein Problem im JavaFX WebView-Browser, bei dem immer wieder
        // Scrollbars ein- und wieder ausgeblendet wurden. Es trat in Verbindung mit Masonry
        // auf, wenn mehrere Elemente auf einer Seite angezeigt wurden (u.a. ein Chart) und
        // das Layout umgebrochen hat. Da sich die Groesse des Charts sowieso an den Container
        // anpasst sollte overflow: hidden keine weiteren Auswirkungen haben.
        $output = <<<HTML

<div class="exf-grid-item {$this->getMasonryItemClass()}" style="width:{$this->getWidth()};min-width:{$this->getMinWidth()};height:{$this->getHeight()};padding:{$this->getPadding()};box-sizing:border-box;">
    <div class="easyui-panel {$this->buildCssElementClass()}" style="height: auto;" id="{$this->getId()}_wrapper" data-options="fit: true {$panel_options}, onResize: function(){ {$this->getOnResizeScript()} }">
    	{$header_html}
    	{$contentHtml}
    </div>
</div>

HTML;
        
        return $output;
    }

    /**
     * 
     * @return string
     */
    protected function buildJsForPanel() : string
    {
        $widget = $this->getWidget();
        
         // Add Scripts for the configurator widget first as they may be needed for the others
        $configurator_element = $this->getFacade()->getElement($widget->getConfiguratorWidget());
        
        return <<<JS

                    {$configurator_element->buildJs()}
                    {$this->buildJsButtons()}

                    $('#{$configurator_element->getId()}').find('.grid').on( 'layoutComplete', function( event, items ) {
                        setTimeout(function(){
                            var newHeight = $('#{$this->getId()}_wrapper > .panel').height();
                            $('#{$this->getId()}').height($('#{$this->getId()}').parent().height()-newHeight);
                        }, 0);               
                    });
JS;
    }
                    
    protected function buildJsDataLoadFunction() : string
    {
        return <<<JS

function {$this->buildJsDataLoadFunctionName()}(oParams) {
    {$this->buildJsDataLoadFunctionBody()}
}


JS;
    }
        
    protected function buildJsDataLoadFunctionName() : string
    {
        return $this->buildJsFunctionPrefix() . 'LoadData';
    }

    /**
     * Returns the JS code to fetch data: either via AJAX or from a Data widget (if the chart is bound to another data widget).
     *
     * @return string
     */
    protected function buildJsDataLoadFunctionBody(string $oParamsJs = 'oParams') : string
    {
        $widget = $this->getWidget();
        $dataWidget = $this->getDataWidget();
        
        $headers = ! empty($this->getAjaxHeaders()) ? 'headers: ' . json_encode($this->getAjaxHeaders()) . ',' : '';
            
        $url_params = '';
            
        // send sort information
        if (count($dataWidget->getSorters()) > 0) {
            foreach ($dataWidget->getSorters() as $sorter) {
                $sort .= ',' . urlencode($sorter->getProperty('attribute_alias'));
                $order .= ',' . urldecode($sorter->getProperty('direction'));
            }
            $url_params .= '
                        sort: "' . substr($sort, 1) . '",
                        order: "' . substr($order, 1) . '",';
        }
            
        // send pagination/limit information. Charts currently do not support real pagination, but just a TOP-X display.
        if ($dataWidget->isPaged()) {
            $url_params .= '
                        page: 1,
                        rows: ' . $dataWidget->getPaginator()->getPageSize($this->getFacade()->getConfig()->getOption('WIDGET.CHART.PAGE_SIZE')) . ',';
        }
            
        // Loader function
        $configurator_element = $this->getFacade()->getElement($widget->getConfiguratorWidget());
        $output .= <<<JS
					{$this->buildJsBusyIconShow()}

                    try {
                        if (! {$configurator_element->buildJsValidator()}) {
                            {$this->buildJsDataResetter()}
                            {$this->buildJsMessageOverlayShow($dataWidget->getAutoloadDisabledHint())}
                            {$this->buildJsBusyIconHide()}
                            return false;
                        }
                    } catch (e) {
                        console.warn('Could not check filter validity - ', e);
                    }

					return $.ajax({
						url: "{$this->getAjaxUrl()}",
                        method: "POST",
                        {$headers}
                        data: function(){
                            return $.extend(true, {
                                resource: "{$dataWidget->getPage()->getAliasWithNamespace()}", 
                                element: "{$dataWidget->getId()}",
                                object: "{$dataWidget->getMetaObject()->getId()}",
                                action: "{$dataWidget->getLazyLoadingActionAlias()}",
                                {$url_params}
                                data: {$configurator_element->buildJsDataGetter()}
                            }, ({$oParamsJs} || {}));
                        }(),
						success: function(data){
                            var jqSelf = $('#{$this->getId()}');
							{$this->buildJsDataLoaderOnLoaded('data')}
                            {$this->getOnLoadSuccess()}
							{$this->buildJsBusyIconHide()}
						},
						error: function(jqXHR, textStatus, errorThrown){
							{$this->buildJsShowErrorAjax('jqXHR')}
							{$this->buildJsBusyIconHide()}
						}
					});
JS;
        
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
            if (($containerWidget = $widget->getParentByClass('exface\\Core\\Interfaces\\Widgets\\iContainOtherWidgets')) && ($containerWidget->countWidgetsVisible() > 1)) {
                $widget->setHeight($this->getFacade()->getConfig()->getOption('WIDGET.CHART.HEIGHT_DEFAULT'));
            }
        }
        return parent::getHeight();
    }
    
    /**
     * Function to refresh the chart
     *
     * @return string
     */
    public function buildJsRefresh() : string
    {
        return $this->buildJsDataLoadFunctionName() . '();';
    }
    
    /**
     * function to build overlay and show given message
     *
     * @param string $message
     * @return string
     */
    protected function buildJsMessageOverlayShow(string $message) : string
    {
        return '';
    }
    
    /**
     * function to hide overlay message
     *
     * @return string
     */
    protected function buildJsMessageOverlayHide() : string
    {
        return '';        
    }
}