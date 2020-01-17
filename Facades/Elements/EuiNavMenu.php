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
    private $currentPage = null;
    
    public function buildHtml()
    {
        $this->currentPage = $this->getWidget()->getPage();
        $menu = $this->getWidget()->getMenu();
        return $this->buildHtmlMenu($menu);
    }
    
    protected function buildHtmlMenu(array $menu, int $level = 1) : string
    {       
        if ($level === 1) {
            $output = "<ul class='nav_menu'>";
        } else {
            $output = "<ul>";
        }
        foreach ($menu as $node) {
            $url = $this->getFacade()->buildUrlToPage($node->getPageAlias());
            if ($node->hasChildNodes()) {
                if ($node->isAncestorOf($this->currentPage) || $node->isPage($this->currentPage)) {
                    $aClasses = 'active';
                    if ($node->getUid() === $this->getWidget()->getPage()->getId()) {
                        $aClasses .= ' current';
                    }
                    $output .= <<<HTML
                <li class='level{$level} active'>
                    <a class="$aClasses" href='{$url}'>{$node->getName()}</a>
{$this->buildHtmlMenu($node->getChildNodes(), $level+1)}
                </li>
           

HTML;
                } else {
                    $output .= <<<HTML
                <li class='level{$level} closed'>
                    <a href='{$url}'>{$node->getName()}</a>
{$this->buildHtmlMenu($node->getChildNodes(), $level+1)}
                </li>                
                
HTML;
                    
                }
            } elseif ($node->isAncestorOf($this->currentPage) || $node->isPage($this->currentPage)) {
                $output .= <<<HTML
                
                <li class='level{$level} active'>
                    <a style="text-decoration:underline;" href='{$url}'>{$node->getName()}</a>
                </li>

HTML;
            } else {
                $output .= <<<HTML
                
                <li class='level{$level} active'>
                    <a href='{$url}'>{$node->getName()}</a>
                </li>
                
HTML;
            }
        }
        $output .= "</ul>";
        return $output;
    }
    
}