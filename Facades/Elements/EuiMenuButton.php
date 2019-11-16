<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryButtonTrait;
use exface\Core\Widgets\Dialog;
use exface\Core\Widgets\MenuButton;

/**
 * Renders MenuButtons as jEasyUI menu button
 * 
 * @method MenuButton getWidget()
 *
 * @author Andrej Kabachnik
 *        
 */
class EuiMenuButton extends EuiButton
{
    
    use JqueryButtonTrait;

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::init()
     */
    protected function init()
    {
        parent::init();
        $this->setElementType('menubutton');
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiButton::buildHtml()
     */
    public function buildHtml()
    {
        $output = '';
        $widget = $this->getWidget();
        $buttons_html = $this->buildHtmlMenuItems();
        
        switch ($widget->getAlign()) {
            case EXF_ALIGN_LEFT:
                $align_style = 'float: left;';
                break;
            case EXF_ALIGN_RIGHT:
                $align_style = 'float: right;';
                break;
            default:
                $align_style = '';
        }
        
        $hint = str_replace('"', '\"', $widget->getHint());
        
        // Render jEasyUI menubutton
        $output .= <<<HTML
            <a href="javascript:void(0)" id="{$this->getId()}" title="{$hint}" class="easyui-{$this->getElementType()}" data-options="menu:'#{$this->buildHtmlMenuId()}', {$this->buildJsDataOptions()}" style="{$align_style}" {$menu_disabled}>
				{$this->getCaption()}
			</a>
			<div id="{$this->buildHtmlMenuId()}">
				{$buttons_html}
			</div>
HTML;
        
        if (! $widget->getParent()->is('ButtonGroup')) {
            // Hier wird versucht zu unterscheiden wo sich der Knopf befindet. Der Wrapper wird nur benoetigt
            // wenn er sich in einem Dialog befindet, aber nicht als Knopf im Footer, sondern im Inhalt.
            $output = $this->buildHtmlGridItemWrapper($output);
        }
        
        return $output;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiButton::buildHtmlHeadTags()
     */
    public function buildHtmlHeadTags()
    {
        return array_merge(parent::buildHtmlHeadTags(), $this->getFacade()->getElement($this->getWidget()->getMenu())->buildHtmlHeadTags());
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiButton::buildJsDataOptions()
     */
    protected function buildJsDataOptions()
    {
        $options = parent::buildJsDataOptions();
        
        if ($this->getWidget()->getMenu()->isEmpty()) {
            $options .= ', disabled: true, plain: true';
        }
        
        return $options;
    }
    
    public function buildHtmlMenuItems()
    {
        return $this->getFacade()->getElement($this->getWidget()->getMenu())->buildHtmlButtons();
    }

    /**
     *
     * @param string $html            
     * @return string
     */
    protected function buildHtmlGridItemWrapper($html, $title = '')
    {
        $output = '<div class="exf-grid-item ' . $this->getMasonryItemClass() . ' exf-input" title="' . ($title ? $title : trim($this->buildHintText())) . '" style="width: ' . $this->getWidth() . '; min-width: ' . $this->getMinWidth() . '; height: ' . $this->getHeight() . ';">
				' . $html . '
			</div>
			';
        return $output;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJs()
     */
    public function buildJs()
    {
        foreach ($this->getWidget()->getButtons() as $btn){
            $button_js .= $this->getFacade()->getElement($btn)->buildJs();   
        }
        
        return  <<<JS

{$button_js}

JS;
    }

    /**
     *
     * @return string
     */
    protected function buildHtmlMenuId()
    {
        return $this->getId() . '_menu';
    }
}
?>