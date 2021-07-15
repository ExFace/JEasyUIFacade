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
                //if node has child nodes, add them to menu                
                if ($node->isAncestorOf($this->currentPage) || $node->isPage($this->currentPage)) {
                    //if the node is ancestor of current page or is current page style if bold (via 'class="active current"')
                    $aClass= '';                    
                    if ($node->getUid() === $this->getWidget()->getPage()->getUid()) {
                        //if node is current page style it with underline
                        $aStyle .= 'current-leaf';
                    }
                    $output .= <<<HTML
                <li class='level{$level} active'>
                    <a class="active current {$aClass}" href='{$url}' title="{$node->getDescription()}">{$node->getName()}</a>
{$this->buildHtmlMenu($node->getChildNodes(), $level+1)}
                </li>
           

HTML;
                } else {
                    $childNodesHtml = '';
                    if ($this->getWidget()->getExpandAll()) {
                        $childNodesHtml = $this->buildHtmlMenu($node->getChildNodes(), $level+1);
                    }
                    $output .= <<<HTML
                <li class='level{$level} closed'>                    
                    <a href='{$url}' title="{$node->getDescription()}">{$node->getName()}</a>
{$childNodesHtml}
                </li>                
                
HTML;
                    
                }
            } elseif ($node->isPage($this->currentPage)) {
                //if node is node for current page, style it bold (via 'class="active current"') and with underline
                $output .= <<<HTML
                
                <li class='level{$level} active'>
                    <a class="active current current-leaf" href='{$url}' title="{$node->getDescription()}">{$node->getName()}</a>
                </li>

HTML;
            } else {
                $output .= <<<HTML
                
                <li class='level{$level} active'>
                    <a href='{$url}' title="{$node->getDescription()}">{$node->getName()}</a>
                </li>
                
HTML;
            }
        }
        $output .= "</ul>";
        return $output;
    }
    
}