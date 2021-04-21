<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\Menu;

/**
 * 
 * @method Menu getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class EuiMenu extends EuiAbstractElement 
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildHtml()
     */
    public function buildHtml()
    {        
        return <<<HTML
<div class="easyui-panel" data-options="fit:true, title:'{$this->getCaption()}'">
    <div class="easyui-menu" data-options="inline:true, fit:true, lines:true" style="position:relative; border:none">
        {$this->buildHtmlButtons()}
    </div>
</div>
HTML;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJs()
     */
    public function buildJs()
    {
        $buttons_js = '';
        foreach ($this->getWidget()->getButtons() as $btn){
            $buttons_js .= $this->getFacade()->getElement($btn)->buildJs();
        }
        return $buttons_js;
    }
    
    public function buildHtmlButtons()
    {
        $buttons_html = '';
        $last_parent = null;
        foreach ($this->getWidget()->getButtons() as $b) {
            // Insert separators between button groups (neighbouring buttons with
            // different parents.
            if (!is_null($last_parent) && $last_parent !== $b->getParent()){
                $buttons_html .= '<div class="menu-sep"></div>';
            }
            $last_parent = $b->getParent();
            
            // Create a menu entry
            $icon = $b->getIcon() && $b->getShowIcon(true) ? ' iconCls="' . $this->buildCssIconClass($b->getIcon()) . '"' : '';
            $disabled = $b->isDisabled() ? ' disabled=true' : '';
            $buttons_html .= <<<HTML
                <div {$icon} {$disabled} title="{$b->getHint()}" id="{$this->getFacade()->getElement($b)->getId()}" onclick="{$this->getFacade()->getElement($b)->buildJsClickFunctionName()}()">
    				{$b->getCaption()}
    			</div>
HTML;
        }
        return $buttons_html;
    }
}