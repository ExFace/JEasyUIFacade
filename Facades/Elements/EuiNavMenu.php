<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\NavMenu;

/**
 * 
 * @method NavMenu getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class EuiNavMenu extends EuiAbstractElement 
{
    public function buildHtml()
    {
        $menu = $this->getWidget()->getMenu();
            return $this->buildHtmlMenu($menu);
    }
    
    protected function buildHtmlMenu(array $menu, int $level = 1) : string
    {
        //TODO get the link prefix via a function, its hardcoded right now for testing
        
        if ($level === 1) {
            $output = "<ul class='nav_menu'>";
        } else {
            $output = "<ul>";
        }
        foreach ($menu as $node) {
            $url = $this->getFacade()->buildUrlToPage($node->getPageAlias());
            if ($node->hasChildNodes()) {
                $output .= <<<HTML
                <li class='level{$level} active'>
                    <a href='{$url}'>{$node->getName()}</a>
{$this->buildHtmlMenu($node->getChildNodes(), $level+1)}
                </li>
           

HTML;
            } else {
                $output .= <<<HTML
                
                <li class='level{$level} closed'>
                    <a href='{$url}'>{$node->getName()}</a>
                </li>

HTML;
            }
        }
        $output .= "</ul>";
        return $output;
    }
    
}