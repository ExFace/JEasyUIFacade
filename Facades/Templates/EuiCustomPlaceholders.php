<?php
namespace exface\JEasyUIFacade\Facades\Templates;

use exface\Core\Interfaces\Debug\LogBookInterface;
use exface\Core\Interfaces\TemplateRenderers\PlaceholderResolverInterface;
use exface\Core\Interfaces\Facades\FacadeInterface;
use exface\JEasyUIFacade\Facades\JEasyUIFacade;
use exface\Core\Interfaces\Model\UiPageInterface;
use exface\Core\Templates\Placeholders\WidgetRenderPlaceholders;

/**
 * Replaces the [#breadcrumbs#] in jEasyUI templates.
 *
 * @author Andrej Kabachnik
 *
 */
class EuiCustomPlaceholders implements PlaceholderResolverInterface
{
    private $facade = null;
    
    private $page = null;
    
    /**
     *
     * @param FacadeInterface $facade
     * @param string $prefix
     */
    public function __construct(JEasyUIFacade $facade, UiPageInterface $page)
    {
        $this->facade = $facade;
        $this->page = $page;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\TemplateRenderers\PlaceholderResolverInterface::resolve()
     */
    public function resolve(array $placeholders, ?LogBookInterface $logbook = null) : array
    {
        if (in_array('breadcrumbs', $placeholders)) {
            $widgetResolver = new WidgetRenderPlaceholders($this->facade, $this->page, '~widget:');
            $string = $widgetResolver->resolve(['~widget:NavCrumbs'])['~widget:NavCrumbs'];
            $string = preg_replace("/\r|\n/", "", $string);
            return ['breadcrumbs' => str_replace(["'",'"'], "\'", $string)];
        }
        return [];
    }
}