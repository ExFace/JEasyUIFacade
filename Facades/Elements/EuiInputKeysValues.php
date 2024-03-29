<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Interfaces\Actions\ActionInterface;

/**
 * 
 * @author Andrej Kabachnik
 *
 */
class EuiInputKeysValues extends EuiInputText
{
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::getElementType()
     */
    public function getElementType() : ?string
    {
        return 'div';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInputText::buildHtml()
     */
    public function buildHtml()
    {
        return $this->buildHtmlLabelWrapper('<div id="' . $this->getId() . '"></div>');
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInputText::buildJs()
     */
    public function buildJs()
    {
        return <<<JS
        
    $('#{$this->getId()}').jspreadsheet({
        data: {$this->buildJsJExcelData()},
        allowRenameColumn: false,
        allowInsertColumn: false,
        allowDeleteColumn: false,
        allowInsertRow: false,
        allowDeleteRow: false,
        wordWrap: true,
        {$this->buildJsJExcelColumns()}
        minSpareRows: 0,
        onevent: function(event) {
            ({$this->buildJsJqueryElement()}[0].jssPlugins || []).forEach(function(oPlugin) {
                oPlugin.onevent(event);
            });
        }
    });

    {$this->buildJsInitPlugins()}    
    {$this->buildJsFixContextMenuPosition()}
    
JS;
    }
    
    /**
     *
     * @return string
     */
    protected function buildJsInitPlugins() : string
    {
        $pluginsJs = '';
        $cfg = $this->getFacade()->getConfig();
        if ($cfg->hasOption('LIBS.JEXCEL.PLUGINS')) {
            foreach ($cfg->getOption('LIBS.JEXCEL.PLUGINS') as $var => $path) {
                $pluginsJs = "{$var}({$this->buildJsJqueryElement()}[0].jexcel)";
            }
        }
        return <<<JS
        
        {$this->buildJsJqueryElement()}[0].jssPlugins = [
            $pluginsJs
        ];
JS;
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsJExcelData() : string
    {
        $widget = $this->getWidget();
        $values = json_decode($widget->getValue(), true);
        $keys = array_keys($values) ?? [];
        
        foreach ($keys as $key) {
            $row = [$key, $values[$key]];
            foreach ($widget->getReferenceValues() as $refVals) {
                $row[] = $refVals[$key];
            }
            $data[] = $row;
        }
        
        return json_encode($data);
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsJExcelColumns() : string
    {
        $widget = $this->getWidget();
        $columns = [
            [
                'title' => $widget->getCaptionForKeys(),
                'type' => 'text',
                'readOnly' => true,
                'align' => 'left',
                'width' => 'auto'
            ],
            [
                'title' => $widget->getCaptionForValues() ?? $widget->getAttribute()->getName(),
                'type' => 'text',
                'align' => 'left',
                'width' => 'auto'
            ]
        ];
        foreach (array_keys($this->getWidget()->getReferenceValues()) as $title) {
            $columns[] = [
                'title' => $title,
                'type' => 'text',
                'readOnly' => true,
                'align' => 'left',
                'width' => 'auto'
            ];
        }
        
        return "
        columns: " . json_encode($columns) . ",";
    }
    
    /**
    *
    * @return string[]
    */
    public function buildHtmlHeadTags() : array
    {
        $includes = array_merge(
            parent::buildHtmlHeadTags(),
            $this->buildHtmlHeadTagsForJExcel()
        );
        
        array_unshift($includes, '<script type="text/javascript">' . $this->buildJsFixJqueryImportUseStrict() . '</script>');
        
        return $includes;
    }
    
    /**
     *
     * @return string[]
     */
    protected function buildHtmlHeadTagsForJExcel() : array
    {
        $facade = $this->getFacade();
        $includes = [
            '<script type="text/javascript" src="' . $facade->buildUrlToSource('LIBS.JEXCEL.JS') . '"></script>',
            '<script type="text/javascript" src="' . $facade->buildUrlToSource('LIBS.JEXCEL.JS_JSUITES') . '"></script>',
            '<link href="' . $facade->buildUrlToSource('LIBS.JEXCEL.CSS') . '" rel="stylesheet" media="screen">',
            '<link href="' . $facade->buildUrlToSource('LIBS.JEXCEL.CSS_JSUITES') . '" rel="stylesheet" media="screen">'
        ];
        if ($facade->getConfig()->hasOption('LIBS.JEXCEL.PLUGINS')) {
            foreach ($facade->getConfig()->getOption('LIBS.JEXCEL.PLUGINS') as $path) {
                $includes[] = '<script type="text/javascript" src="' . $facade->buildUrlToVendorFile($path) . '"></script>';
            }
        }
        return $includes;
    }
    
    public function buildJsValueGetter()
    {
        return <<<JS
(function(){
    var aData = $('#{$this->getId()}').jexcel('getData', false);
    var oResult = {};
    for (var i = 0; i < aData.length; i++) {
        oResult[aData[i][0]] = (aData[i][1] === '' ? null : aData[i][1]);
    }
    return JSON.stringify(oResult);
}())
JS;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJsDataGetter()
     */
    public function buildJsDataGetter(ActionInterface $action = null)
    {
        return parent::buildJsDataGetter($action);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsDataSetter()
     */
    public function buildJsDataSetter(string $jsData) : string
    {
        return parent::buildJsDataSetter($jsData);
    }
    
    /**
     *
     * @return string
     */
    protected function buildJsFixContextMenuPosition() : string
    {
        // Move contex menu to body to fix positioning errors when there is a parent with position:relative
        return "{$this->buildJsJqueryElement()}.find('.jexcel_contextmenu').detach().addClass('exf-partof-{$this->getId()}').appendTo($('body'));";
    }
    
    /**
     * Remove 'use strict'; from all JS files loaded via jQuery.ajax because otherwise they
     * won't be able to create global variables, which will prevent many vanilla-js libs
     * from working (e.g. jExcel)
     *
     * @return string
     */
    protected function buildJsFixJqueryImportUseStrict() : string
    {
        return <<<JS
        
$.ajaxSetup({
    dataFilter: function(data, type) {
        if (type === 'script') {
        	var regEx = /['"]use strict['"];/;
        	if (regEx.test(data.substring(0, 500)) === true) {
            	data = data.replace(regEx, '');
        	}
        }
        return data;
    }
});

JS;
    }
    
    /**
     * Returns the jQuery element for jExcel - e.g. $('#element_id') in most cases.
     * @return string
     */
    protected function buildJsJqueryElement() : string
    {
        return "$('#{$this->getId()}')";
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJsSetRequired()
     */
    protected function buildJsSetRequired(bool $required) : string
    {
        // TODO add implementation of required_if
        return "";
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJsRequiredGetter()
     */
    protected function buildJsRequiredGetter() : string
    {
        return $this->getWidget()->isRequired() ? "true" : "false";
    }
}