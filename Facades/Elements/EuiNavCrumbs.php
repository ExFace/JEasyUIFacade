<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\Breadcrumbs;

/**
 * 
 * @method Breadcrumbs getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class EuiNavCrumbs extends EuiAbstractElement 
{
    public function buildHtml()
    {
        $breadcrumbs = $this->getWidget()->getBreadcrumbs();
        return $this->buildHtmlBreadcrumbs($breadcrumbs);
    }
    
    protected function buildHtmlBreadcrumbs(array $menu) : string
    {
        if (count($menu)=== 0) {
            return '';
        } else {
            $node = $menu[0];
            $output = <<<HTML

<div>

HTML;
        
        //add all breadcrumbs leading to leaf page
            while ($node->hasChildNodes() === true) {
                $url = $this->getFacade()->buildUrlToPage($node->getPageAlias());
                $output .= <<<HTML
    <a style="text-decoration:underline;" href='{$url}'>{$node->getName()}</a> »&nbsp;           

HTML;
                           
                $node = $node->getChildNodes()[0];
            }
        
        //add breadcrumb for leaf page
            $output .= <<<HTML
    {$node->getName()}
</div>

HTML;
            return $output;
        }
    }
    
}