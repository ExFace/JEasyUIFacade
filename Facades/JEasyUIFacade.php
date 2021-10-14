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
use exface\Core\Interfaces\Exceptions\AuthorizationExceptionInterface;
use exface\Core\Exceptions\Contexts\ContextOutOfBoundsError;
use exface\Core\Exceptions\OutOfBoundsException;
use exface\Core\Exceptions\Security\AccessDeniedError;
use exface\Core\Factories\UiPageFactory;
use exface\Core\Factories\WidgetFactory;
use exface\Core\CommonLogic\UxonObject;
use exface\Core\Exceptions\RuntimeException;
use exface\Core\Exceptions\InvalidArgumentException;

class JEasyUIFacade extends AbstractAjaxFacade
{
    private $theme_css = null;
    
    private $theme_class = null;
    
    private $theme_header_color = null;
    
    private $theme_sidebar_color = null;
    
    private $theme_sidebar_collapsed = null;
    
    private $theme_emphasis_color = null;
    
    private $theme_link_color = null;
    
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
        $includes = $this->buildHtmlHeadThemeIncludes();
        
        $includes[] = '<script type="text/javascript" src="' . $this->buildUrlToSource('LIBS.JQUERY') . '"></script>';
        $includes[] = '<script type="text/javascript" src="' . $this->buildUrlToSource('LIBS.JEASYUI.CORE') . '"></script>';
        $includes[] = '<script type="text/javascript" src="' . $this->buildUrlToSource('LIBS.JEASYUI.LANG_DEFAULT') . '"></script>';
        $includes[] = '<script type="text/javascript" src="' . $this->buildUrlToSource('LIBS.JEASYUI.FACADE_ADDONS.JS') . '"></script>';
        $includes[] = '<link href="' . $this->buildUrlToSource('LIBS.FONT_AWESOME') . '" rel="stylesheet" type="text/css" />';
        
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
     * @throws OutOfBoundsException
     * @return string[]
     */
    protected function buildHtmlHeadThemeIncludes() : array
    {
        $arr = [];
        foreach ($this->getThemeCss() as $path) {
            $arr[] = '<link rel="stylesheet" type="text/css" href="' . $this->buildUrlToVendorFile($path) . '">';
        }
        return $arr;
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
        $data['rows'] = $data_sheet->getRowsDecrypted();
        $data['offset'] = $data_sheet->getRowsOffset();
        $data['total'] = $data_sheet->countRowsInDataSource();
        $data['footer'] = $data_sheet->getTotalsRows();
        return $data;
    }
    
    protected function buildHtmlFromError(ServerRequestInterface $request, \Throwable $exception, UiPageInterface $page = null) : string
    {
        if ($this->isShowingErrorDetails() === false && ! ($exception instanceof AuthenticationFailedError) && ! ($exception instanceof AuthorizationExceptionInterface && $this->getWorkbench()->getSecurity()->getAuthenticatedToken()->isAnonymous())) {
            $body = '';
            try {
                $headTags = implode("\n", $this->buildHtmlHeadCommonIncludes());
                $action = '';
                if ($exception instanceof ExceptionInterface) {
                    $title = $exception->getMessageType($this->getWorkbench()) . ' ' . $exception->getAlias();
                    $message = $exception->getMessageTitle($this->getWorkbench());
                    $details = $exception->getMessage();
                    if ($exception instanceof AccessDeniedError) {
                        $page = $page ?? UiPageFactory::createEmpty($this->getWorkbench());
                        $logoutBtn = WidgetFactory::createFromUxon($page, new UxonObject([
                            'widget_type' => 'Button',
                            'object_alias' => 'exface.Core.DUMMY',
                            'action_alias' => 'exface.Core.Logout'
                        ]));
                        $logoutEl = $this->getElement($logoutBtn);
                        $action = <<<HTML

<script type="text/javascript">

    {$logoutEl->buildJs()}

</script>
<a href="javascript:{$logoutEl->buildJsClickFunctionName()}()">{$this->getApp()->getTranslator()->translate('ERROR.LOGOUT_TO_CHANGE_USER')}</a>.

HTML;
                    }
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
        <p>{$action}</p>
    </div>
</div>

HTML;
                $body = $headTags. "\n" . $errorBody;
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
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\AbstractAjaxFacade::buildHtmlPage($widget)
     */
    protected function buildHtmlPage(WidgetInterface $widget, string $pagetTemplateFilePath = null) : string
    {
        $pagetTemplateFilePath = $pagetTemplateFilePath ?? $this->getPageTemplateFilePath();
        $renderer = new EuiFacadePageTemplateRenderer($this, $pagetTemplateFilePath, $widget);
        return $renderer->render();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\AbstractAjaxFacade::getPageTemplateFilePathDefault()
     */
    protected function getPageTemplateFilePathDefault() : string
    {
        return $this->getApp()->getDirectoryAbsolutePath() . DIRECTORY_SEPARATOR . 'Facades' . DIRECTORY_SEPARATOR . 'Templates' . DIRECTORY_SEPARATOR . 'EuiDefaultTemplate.html';
    }
    
    /**
     * Returns the path to the unauthorized-page template file (absolute or relative to the vendor folder)
     *
     * @return string
     */
    protected function getPageTemplateFilePathForUnauthorized() : string
    {
        return $this->getApp()->getDirectoryAbsolutePath() . DIRECTORY_SEPARATOR . 'Facades' . DIRECTORY_SEPARATOR . 'Templates' . DIRECTORY_SEPARATOR . 'EuiMessagePageTemplate.html';
    }
    
    /**
     *
     * @return string
     */
    public function getThemeClass() : string
    {
        return $this->theme_class ?? 'default';
    }
    
    /**
     * The class of the color theme (used as additional CSS class on <body> element).
     * 
     * E.g. if the theme class is `material`, the `<body>` element will get the CSS class 
     * `theme-material` instead of `theme-default`.
     *
     * @uxon-property theme_class
     * @uxon-type string
     * @uxon-default default
     *
     * @param string $name
     * @return JEasyUIFacade
     */
    protected function setThemeClass(string $name) : JEasyUIFacade
    {
        $this->theme_class = mb_strtolower($name);
        return $this;
    }

    /**
     * The color of the page header
     * 
     * @uxon-property theme_header_color
     * @uxon-type color
     * 
     * @return string
     */
    public function getThemeHeaderColor() : string
    {
        return $this->theme_header_color ?? $this->getConfig()->getOption('THEME.HEADER_COLOR') ?? '';
    }
    
    /**
     * 
     * @param string $value
     * @return JEasyUIFacade
     */
    protected function setThemeHeaderColor(string $value) : JEasyUIFacade
    {
        $this->theme_header_color = $value;
        return $this;
    }
    
    /**
     * The color of the sidebar
     *
     * @uxon-property sidebar_color
     * @uxon-type color
     *
     * @return string
     */
    public function getThemeSidebarColor() : string
    {
        return $this->theme_sidebar_color ?? $this->getConfig()->getOption('THEME.SIDEBAR_COLOR') ?? '';
    }
    
    /**
     *
     * @param string $value
     * @return JEasyUIFacade
     */
    protected function setThemeSidebarColor(string $value) : JEasyUIFacade
    {
        $this->theme_sidebar_color = $value;
        return $this;
    }
    
    /**
     * The color of emphasised controls (e.g. promoted buttons)
     *
     * @uxon-property emphasis_color
     * @uxon-type color
     *
     * @return string
     */
    public function getThemeEmphasisColor() : string
    {
        return $this->theme_emphasis_color ?? $this->getConfig()->getOption('THEME.EMPHASIS_COLOR') ?? '';
    }
    
    /**
     *
     * @param string $value
     * @return JEasyUIFacade
     */
    protected function setThemeEmphasisColor(string $value) : JEasyUIFacade
    {
        $this->theme_emphasis_color = $value;
        return $this;
    }
    
    /**
     * The color of links and buttons
     *
     * @uxon-property link_color
     * @uxon-type color
     *
     * @return string
     */
    public function getThemeLinkColor() : string
    {
        return $this->theme_link_color ?? $this->getConfig()->getOption('THEME.LINK_COLOR') ?? '';
    }
    
    /**
     *
     * @param string $value
     * @return JEasyUIFacade
     */
    protected function setThemeLinkColor(string $value) : JEasyUIFacade
    {
        $this->theme_link_color = $value;
        return $this;
    }
    
    /**
     * The color of links and buttons
     *
     * @uxon-property sidebar_collapsed
     * @uxon-type boolean
     * @uxon-default false
     *
     * @return string
     */
    public function getSidebarCollapsed() : int
    {
        return ($this->theme_sidebar_collapsed ?? $this->getConfig()->getOption('THEME.SIDEBAR_COLLAPSED') ?? false) ? 1 : 0;
    }
    
    /**
     *
     * @param string $value
     * @return JEasyUIFacade
     */
    protected function setSidebarCollapsed(bool $value) : JEasyUIFacade
    {
        $this->theme_sidebar_collapsed = $value;
        return $this;
    }
    
    /**
     * 
     * @return string[]
     */
    public function getThemeCss() : array
    {
        return $this->theme_css ?? $this->getConfig()->getOption('THEME.CSS')->toArray();
    }
    
    /**
     * Array of CSS files to include on the page
     * 
     * @uxon-property theme_css
     * @uxon-type array
     * @uxon-template ["exface/JEasyUIFacade/Facades/js/themes/jeasyui.exface.css",""]
     * 
     * @param UxonObject|array $value
     * @throws InvalidArgumentException
     * @return JEasyUIFacade
     */
    protected function setThemeCss($arrayOrUxon) : JEasyUIFacade
    {
        switch (true) {
            case $arrayOrUxon instanceof UxonObject:
                $this->theme_css = $arrayOrUxon->toArray();
                break;
            case is_array($arrayOrUxon):
                $this->theme_css = $arrayOrUxon;
                break;
            default: 
                throw new InvalidArgumentException('Invalid value for `include_css` property of JEasyUI facade: expecting UXON or PHP array');
        }
        return $this;
    }
}