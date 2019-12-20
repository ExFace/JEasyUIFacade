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
class EuiNavMenu extends EuiWidgetGrid 
{
    public function buildHtml()
    {
        $menu = $this->getWidget()->getMenuArray();
        $output = <<<HTML

    <!-- OWN MENU -->
    <div class="easyui-accordion" data-options="fit:true,border:false">
        <div title="Menü" data-options="selected:true" style="">
            {$this->buildHtmlMenu($menu)}
        </div>
    </div>


HTML;
        return $output;
    }
    
    protected function buildHtmlMenu(array $menu, int $level = 1) : string
    {
        //TODO get the link prefix via by a function, its hardcoded right now for testing
        if ($level === 1) {
            $output = "<ul class='nav_menu'>";
        } else {
            $output = "<ul>";
        }
        foreach ($menu as $item) {
            if (array_key_exists('SUB_MENU', $item)) {
                $output .= <<<HTML
                <li class='level{$level} active'>
                    <a href='/exface/{$item['ALIAS']}.html'>{$item['NAME']}</a>
{$this->buildHtmlMenu($item['SUB_MENU'], $level+1)}
                </li>
           

HTML;
            } else {
                $output .= <<<HTML
                
                <li class='level{$level} closed'>
                    <a href='/exface/{$item['ALIAS']}.html'>{$item['NAME']}</a>
                </li>

HTML;
            }
        }
        $output .= "</ul>";
        return $output;
    }
    
}