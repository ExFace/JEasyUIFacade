<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\NavCrumbs;

/**
 * 
 * @method NavCrumbs getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class EuiNavCrumbs extends EuiAbstractElement 
{
    private $currentPage = null;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::buildHtml()
     */
    public function buildHtml()
    {
        $this->currentPage = $this->getWidget()->getPage();
        $breadcrumbs = $this->getWidget()->getBreadcrumbs();
        if (empty($breadcrumbs) === true) {
            return '';
        }
        $output = <<<HTML
        
<div class="{$this->buildCssElementClass()}">
HTML;
        $output .= $this->buildHtmlBreadcrumbs($breadcrumbs);
        
        $output .= <<<HTML
        
</div>
HTML;
        
        return $output;
    }
    
    /**
     * 
     * @param array $menu
     * @return string
     */
    protected function buildHtmlBreadcrumbs(array $menu) : string
    {
        $output = '';
        foreach($menu as $node) {
            if ($node->isAncestorOf($this->currentPage)) {
                $url = $this->getFacade()->buildUrlToPage($node->getPageAlias());
                $output .= <<<HTML
                
    <a href='{$url}'>{$node->getName()}</a> »&nbsp;
HTML;
                if ($node->hasChildNodes()) {
                    $output .= $this->buildHtmlBreadcrumbs($node->getChildNodes());
                }
                break;
            } elseif ($node->isPage($this->currentPage)) {
                $output .= "{$node->getName()}";
                break;
            }
        }
        return $output;
        
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildCssElementClass()
     */
    public function buildCssElementClass() : string
    {
        return 'exf-navcrumbs';
    }
    
}