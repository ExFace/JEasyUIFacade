<?php
namespace exface\JEasyUIFacade\Facades;

use exface\Core\Facades\AbstractAjaxFacade\AbstractAjaxFacade;
use exface\Core\Exceptions\DependencyNotFoundError;
use exface\JEasyUIFacade\Facades\Middleware\EuiDatagridUrlParamsReader;
use exface\Core\Interfaces\DataSheets\DataSheetInterface;
use exface\Core\Interfaces\WidgetInterface;
use Psr\Http\Message\ServerRequestInterface;
use exface\Core\Interfaces\Model\UiPageInterface;
use GuzzleHttp\Psr7\Response;
use exface\Core\Interfaces\Exceptions\ExceptionInterface;
use exface\JEasyUIFacade\Facades\Templates\EuiFacadePageTemplateRenderer;
use exface\Core\Exceptions\Security\AuthenticationFailedError;

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
        
        $config = $this->getConfig();
        $patches = $config->getOption('LIBS.JEASYUI.PATCHES');
        if (! empty($patches)) {
            foreach (explode(',', $patches) as $patch) {
                $includes[] = '<script type="text/javascript" src="' . $this->buildUrlToVendorFile($patch) . '"></script>';
            }
        }
        
        $includes = array_merge($includes, $this->buildHtmlHeadIcons());
        
        if ($config->getOption('FACADE.AJAX.CACHE_SCRIPTS') === true) {
            $includes[] = '<script type="text/javascript">
$.ajaxPrefilter(function( options ) {
	if ( options.type==="GET" && options.dataType ==="script" ) {
		options.cache=true;
	}
});
</script>';
        }
        
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
            $widgetClass = $this->getElementClassForWidget($widget);
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
    
    protected function buildHtmlFromError(ServerRequestInterface $request, \Throwable $exception, UiPageInterface $page = null) : string
    {
        if ($this->isShowingErrorDetails() === false && ! ($exception instanceof AuthenticationFailedError)) {
            $body = '';
            try {
                $mode = $request->getAttribute($this->getRequestAttributeForRenderingMode(), static::MODE_FULL);
                $headTags = implode("\n", $this->buildHtmlHeadCommonIncludes());
                if ($exception instanceof ExceptionInterface) {
                    $title = $exception->getMessageType($this->getWorkbench()) . ' ' . $exception->getAlias();
                    $message = $exception->getMessageTitle($this->getWorkbench());
                    $details = $exception->getMessage();
                } else {
                    $title = 'Internal Error';
                    $message = $exception->getMessage();
                    $details = '';
                }
                $errorBody = <<<HTML

<div style="width: 100%; height: 100%; position: relative;">
    <div style="width: 300px;position: absolute;top: 30%;left: calc(50% - 150px);">
        <h1>{$title}</h1>
        <p>{$message}</p>
        <p style="color: grey; font-style: italic;">{$details}</p>
    </div>
</div>

HTML;
                switch (true) {
                    case $mode === static::MODE_HEAD:
                        $body = $headTags;
                        break;
                    case $mode === static::MODE_BODY:
                        $body = $errorBody;
                        break;
                    default:
                        $body = $headTags. "\n" . $errorBody;
                }
            } catch (\Throwable $e) {
                // If anything goes wrong when trying to prettify the original error, drop prettifying
                // and throw the original exception wrapped in a notice about the failed prettification
                $this->getWorkbench()->getLogger()->logException($e);
                $log_id = $e instanceof ExceptionInterface ? $e->getId() : '';
                throw new RuntimeException('Failed to create error report widget: "' . $e->getMessage() . '" - see ' . ($log_id ? 'log ID ' . $log_id : 'logs') . ' for more details! Find the orignal error detail below.', null, $exception);
            }
            
            return $body;
        }
        return parent::buildHtmlFromError($request, $exception, $page);
    }
    
    protected function buildHtmlPage(WidgetInterface $widget) : string
    {
        if ($widget->getPage()->getAlias() === 'login') {
            $tpl = $this->getPageTemplateFilePathLogin();
        } else {
            $tpl = $this->getPageTemplateFilePath();
        }
        $renderer = new EuiFacadePageTemplateRenderer($this, $tpl, $widget);
        return $renderer->render();
    }
    
    protected function getPageTemplateFilePathDefault() : string
    {
        return $this->getApp()->getDirectoryAbsolutePath() . DIRECTORY_SEPARATOR . 'Facades' . DIRECTORY_SEPARATOR . 'Templates' . DIRECTORY_SEPARATOR . 'EuiDefaultTemplate.html';
    }
    
    protected function getPageTemplateFilePathLogin() : string
    {
        return $this->getApp()->getDirectoryAbsolutePath() . DIRECTORY_SEPARATOR . 'Facades' . DIRECTORY_SEPARATOR . 'Templates' . DIRECTORY_SEPARATOR . 'EuiLoginTemplate.html';
    }
}