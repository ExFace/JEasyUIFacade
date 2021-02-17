<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\LeafletTrait;
use exface\JEasyUIFacade\Facades\Elements\Traits\EuiDataElementTrait;
use exface\Core\Interfaces\Widgets\iShowData;
use exface\Core\Widgets\Parts\Maps\AbstractDataLayer;

/**
 * 
 * @method \exface\Core\Widgets\Map getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class EuiMap extends EuiData
{
    use LeafletTrait;
    use EuiDataElementTrait;

    protected function init()
    {
        parent::init();
        $widget = $this->getWidget();
        
        // Disable global buttons because jEasyUI charts do not have data getters yet
        $widget->getToolbarMain()->setIncludeGlobalActions(false);
        
        $this->fireRendererExtendedEvent($widget); 
        $this->registerDefaultLayerRenderers();
        
        $this->addOnResizeScript($this->buildJsResize());
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiData::buildHtml()
     */
    public function buildHtml() : string
    {        
        return $this->buildHtmlPanelWrapper($this->buildHtmlLeafletDiv('400px'));
    }
    
    protected function getDataWidget() : iShowData
    {
        foreach ($this->getWidget()->getLayers() as $layer) {
            if ($layer instanceof AbstractDataLayer) {
                return $layer->getDataWidget();
            }
        }
        return null;
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
                    {$this->buildJsDataLoadFunction()}

                    var {$this->buildJsLeafletVar()};
                    setTimeout(function(){
                        {$this->buildJsLeafletInit()}
                        {$this->buildJsResize()}
                    });

JS;
    }
    
    /**
     * Returns the JS code to resize the map to fill out the current wrapper panel size.
     * @return string
     */
    protected function buildJsResize() : string
    {
        return <<<JS

                        setTimeout(function() {
                             var newHeight = $('#{$this->getId()}_wrapper > .panel').height();
                             $('#{$this->getId()}').height($('#{$this->getId()}').parent().height() - newHeight);
                             {$this->buildJsLeafletResize()}
                        },100);
JS;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildHtmlHeadTags()
     */
    public function buildHtmlHeadTags()
    {
        $includes = $this->buildHtmlHeadTagsLeaflet();
        
        // masonry for proper filter alignment
        $includes[] = '<script type="text/javascript" src="' . $this->getFacade()->buildUrlToSource('LIBS.MASONRY') . '"></script>';
        return $includes;
    }
    
    protected function buildJsDataLoaderOnLoaded(string $dataJs): string
    {
        return '';
    }
    
    /**
     * Function to refresh the chart
     *
     * @return string
     */
    public function buildJsRefresh() : string
    {
        return $this->buildJsLeafletRefresh();
    }
}