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
    
    public function buildHtml()
    {
        $this->currentPage = $this->getWidget()->getPage();
        $breadcrumbs = $this->getWidget()->getBreadcrumbs();
        if (empty($breadcrumbs) === true) {
            return '';
        }
        $output = <<<HTML
        
<div>
HTML;
        $output .= $this->buildHtmlBreadcrumbs($breadcrumbs);
        
        $output .= <<<HTML
        
<div>
HTML;
        
        return $output;
    }
    
    protected function buildHtmlBreadcrumbs(array $menu) : string
    {
        $output = '';
        foreach($menu as $node) {
            if ($node->isAncestorOf($this->currentPage)) {
                $url = $this->getFacade()->buildUrlToPage($node->getPageAlias());
                $output .= <<<HTML
                
    <a style="text-decoration:underline;" href='{$url}'>{$node->getName()}</a> »&nbsp;
HTML;
                if ($node->hasChildNodes()) {
                    $output .= $this->buildHtmlBreadcrumbs($node->getChildNodes());
                }
                break;
            } elseif ($node->isPage($this->currentPage)) {
                $output .= "{$node->getName()}";
            }
        }
        return $output;
        
    }
    
}