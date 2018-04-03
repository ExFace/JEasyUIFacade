<?php
namespace exface\JEasyUiTemplate\Templates;

use exface\Core\Templates\AbstractAjaxTemplate\AbstractAjaxTemplate;
use exface\Core\Exceptions\DependencyNotFoundError;
use exface\JEasyUiTemplate\Templates\Middleware\euiDatagridUrlParamsReader;

class JEasyUiTemplate extends AbstractAjaxTemplate
{

    public function init()
    {
        parent::init();
        $this->setClassPrefix('eui');
        $this->setClassNamespace(__NAMESPACE__);
        $folder = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'jeasyui';
        if (! is_dir($folder)) {
            throw new DependencyNotFoundError('jEasyUI files not found! Please install jEasyUI to "' . $folder . '"!', '6T6HUFO');
        }
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\AbstractAjaxTemplate::getMiddleware()
     */
    protected function getMiddleware() : array
    {
        $middleware = parent::getMiddleware();
        $middleware[] = new euiDatagridUrlParamsReader($this, 'getInputData', 'setInputData');
        return $middleware;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Templates\HttpTemplateInterface::getUrlRoutePatterns()
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
     * @see \exface\Core\Templates\AbstractAjaxTemplate\AbstractAjaxTemplate::buildHtmlHeadCommonIncludes()
     */
    public function buildHtmlHeadCommonIncludes() : array
    {
        $includes = [
            '<link rel="stylesheet" type="text/css" href="' . $this->buildUrlToSource('SOURCES.JEASYUI.THEME') . '">',
            '<link rel="stylesheet" type="text/css" href="' . $this->buildUrlToSource('SOURCES.TEMPLATE.CSS') . '">',
            '<script type="text/javascript" src="' . $this->buildUrlToSource('SOURCES.JQUERY') . '"></script>',
            '<script type="text/javascript" src="' . $this->buildUrlToSource('SOURCES.JEASYUI.CORE') . '"></script>',
            '<script type="text/javascript" src="' . $this->buildUrlToSource('SOURCES.JEASYUI.LANG_DEFAULT') . '"></script>',
            '<script type="text/javascript" src="' . $this->buildUrlToSource('SOURCES.TEMPLATE.JS') . '"></script>',
            '<link href="' . $this->buildUrlToSource('SOURCES.FONT_AWESOME') . '" rel="stylesheet" type="text/css" />'
        ];
        
        // FIXME get the correct lang include accoring to the user's language
        
        $patches = $this->getConfig()->getOption('SOURCES.JEASYUI.PATCHES');
        if (! empty($patches)) {
            foreach (explode(',', $patches) as $patch) {
                $includes[] = '<script type="text/javascript" src="' . $this->getWorkbench()->getCMS()->buildUrlToInclude($patch) . '"></script>';
            }
        }
        
        return $includes;        
    }
}
?>