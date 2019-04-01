<?php
namespace exface\JEasyUIFacade\Facades;

use exface\Core\Facades\AbstractAjaxFacade\AbstractAjaxFacade;
use exface\Core\Exceptions\DependencyNotFoundError;
use exface\JEasyUIFacade\Facades\Middleware\EuiDatagridUrlParamsReader;
use exface\Core\Interfaces\DataSheets\DataSheetInterface;
use exface\Core\Interfaces\WidgetInterface;

class JEasyUIFacade extends AbstractAjaxFacade
{

    public function init()
    {
        parent::init();
        $this->setClassPrefix('Eui');
        $this->setClassNamespace(__NAMESPACE__);
        $folder = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'jeasyui';
        if (! is_dir($folder)) {
            throw new DependencyNotFoundError('jEasyUI files not found! Please install jEasyUI to "' . $folder . '"!', '6T6HUFO');
        }
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\AbstractAjaxFacade::getMiddleware()
     */
    protected function getMiddleware() : array
    {
        $middleware = parent::getMiddleware();
        $middleware[] = new EuiDatagridUrlParamsReader($this, 'getInputData', 'setInputData');
        return $middleware;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Facades\HttpFacadeInterface::getUrlRoutePatterns()
     */
    public function getUrlRoutePatterns() : array
    {
        return [
            "/[\?&]tpl=jeasyui/",
            "/\/api\/jeasyui[\/?]/"
        ];
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\AbstractAjaxFacade::buildHtmlHeadCommonIncludes()
     */
    public function buildHtmlHeadCommonIncludes() : array
    {
        $includes = [
            '<link rel="stylesheet" type="text/css" href="' . $this->buildUrlToSource('LIBS.JEASYUI.THEME') . '">',
            '<link rel="stylesheet" type="text/css" href="' . $this->buildUrlToSource('LIBS.FACADE.CSS') . '">',
            '<script type="text/javascript" src="' . $this->buildUrlToSource('LIBS.JQUERY') . '"></script>',
            '<script type="text/javascript" src="' . $this->buildUrlToSource('LIBS.JEASYUI.CORE') . '"></script>',
            '<script type="text/javascript" src="' . $this->buildUrlToSource('LIBS.JEASYUI.LANG_DEFAULT') . '"></script>',
            '<script type="text/javascript" src="' . $this->buildUrlToSource('LIBS.FACADE.JS') . '"></script>',
            '<link href="' . $this->buildUrlToSource('LIBS.FONT_AWESOME') . '" rel="stylesheet" type="text/css" />'
        ];
        
        // FIXME get the correct lang include accoring to the user's language
        
        $patches = $this->getConfig()->getOption('LIBS.JEASYUI.PATCHES');
        if (! empty($patches)) {
            foreach (explode(',', $patches) as $patch) {
                $includes[] = '<script type="text/javascript" src="' . $this->getWorkbench()->getCMS()->buildUrlToInclude($patch) . '"></script>';
            }
        }
        
        $includes = array_merge($includes, $this->buildHtmlHeadIcons());
        
        return $includes;        
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\AbstractAjaxFacade::buildResponseData()
     */
    public function buildResponseData(DataSheetInterface $data_sheet, WidgetInterface $widget = null)
    {
        // If we need data for a specific widget, see if it's element has a statc data builder method.
        // This way, we can place data builder logic inside elements with special requirements 
        // (e.g. treegrid or privotgrid). Using static methods means, the element does not need to
        // get instantiated - this is not required and may cause significant overhead because
        // the init() methods of all elements would be called (registering event listeners, etc.)
        if ($widget !== null) {
            $widgetClass = $this->getClass($widget);
            if (method_exists($widgetClass, 'buildResponseData') === true) {
                return $widgetClass::buildResponseData($this, $data_sheet, $widget);
            }
        }        
        
        $data = array();
        $data['rows'] = $data_sheet->getRows();
        $data['offset'] = $data_sheet->getRowsOffset();
        $data['total'] = $data_sheet->countRowsInDataSource();
        $data['footer'] = $data_sheet->getTotalsRows();
        return $data;
    }
}
?>