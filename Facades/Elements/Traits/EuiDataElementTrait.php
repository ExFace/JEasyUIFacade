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
                 {$this->buildJsResizeInnerWidget()}
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
        $widget = $this->getWidget();
        
        $header_html = $customHeaderHtml ?? $this->buildHtmlTableHeader();
        
        $hint = $this->buildHintText($widget->getHint(), false);
        $title = $this->escapeString($this->getCaption(), false, true);
        if ($hint && $title) {
            if (strpos($hint, "'") !== false) {
                $hint = str_replace("'", "`", $hint);
            }
            $hint = $this->escapeString($hint, false, true);
            $hint = str_replace("\n", "\\n", $hint);
            $titleWithHint = "<span title=\'{$hint}\'>{$title}</span>";
        } else {
            $titleWithHint = $title;
        }
        $panel_options = ", title: '{$titleWithHint}'";
        
        $gridItemClass = $this->buildCssElementClass();
        if ($this->getWidget()->getHideHeader()) {
            $gridItemClass .= ' exf-data-hide-header';
        }
        
        // Create the panel for the data widget
        // overflow: hidden loest ein Problem im JavaFX WebView-Browser, bei dem immer wieder
        // Scrollbars ein- und wieder ausgeblendet wurden. Es trat in Verbindung mit Masonry
        // auf, wenn mehrere Elemente auf einer Seite angezeigt wurden (u.a. ein Chart) und
        // das Layout umgebrochen hat. Da sich die Groesse des Charts sowieso an den Container
        // anpasst sollte overflow: hidden keine weiteren Auswirkungen haben.
        $output = <<<HTML

<div class="exf-grid-item exf-data-widget {$gridItemClass} {$this->getMasonryItemClass()}" style="width:{$this->getWidth()};min-width:{$this->getMinWidth()};height:{$this->getHeight()};padding:{$this->getPadding()};box-sizing:border-box;">
    <div class="easyui-panel" style="height: auto; overflow-y: hidden;" id="{$this->getId()}_wrapper" data-options="fit: true {$panel_options}, onResize: function(){ {$this->getOnResizeScript()} }">
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

                    {$configurator_element->buildJsForPanelHeader()}
                    {$this->buildJsButtons()}

                    $('#{$configurator_element->getIdOfHeaderPanel()}').find('.grid').on('layoutComplete', function( event, items ) {
                        setTimeout(function(){
                            {$this->buildJsResizeInnerWidget()}
                        }, 0);               
                    });
JS;
    }
    
    protected function buildJsResizeInnerWidget(string $elementId = null) : string
    {
        $elementId = $elementId ?? $this->getId(); 
        return <<<JS

                    var newHeight = $('#{$this->getId()}_wrapper').innerHeight();
                    $('#{$this->getId()}').height(newHeight - $('#{$this->getFacade()->getElement($this->getWidget()->getConfiguratorWidget())->getIdOfHeaderPanel()}').outerHeight());

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