<?php
namespace exface\JEasyUiTemplate\Template\Elements;

use exface\AbstractAjaxTemplate\Template\Elements\JqueryButtonTrait;
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
class euiMenuButton extends euiButton
{
    
    use JqueryButtonTrait;

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\AbstractAjaxTemplate\Template\Elements\AbstractJqueryElement::init()
     */
    protected function init()
    {
        parent::init();
        $this->setElementType('menubutton');
    }

    /**
     *
     * @see \exface\JEasyUiTemplate\Template\Elements\abstractWidget::generateHtml()
     */
    public function generateHtml()
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
        
        // Render jEasyUI menubutton
        $output .= <<<HTML
            <a href="javascript:void(0)" id="{$this->getId()}" class="easyui-{$this->getElementType()}" data-options="menu:'#{$this->buildHtmlMenuId()}', {$this->buildJsDataOptions()}" style="{$align_style}" {$menu_disabled}>
				{$widget->getCaption()}
			</a>
			<div id="{$this->buildHtmlMenuId()}">
				{$buttons_html}
			</div>
HTML;
        
        if (! $widget->getParent()->is('ButtonGroup')) {
            // Hier wird versucht zu unterscheiden wo sich der Knopf befindet. Der Wrapper wird nur benoetigt
            // wenn er sich in einem Dialog befindet, aber nicht als Knopf im Footer, sondern im Inhalt.
            $output = $this->buildHtmlWrapperDiv($output);
        }
        
        return $output;
    }
    
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
        return $this->getTemplate()->getElement($this->getWidget()->getMenu())->buildHtmlButtons();
    }

    /**
     *
     * @param string $html            
     * @return string
     */
    protected function buildHtmlWrapperDiv($html)
    {
        $output = '<div class="fitem ' . $this->getMasonryItemClass() . ' exf_input" title="' . trim($this->buildHintText()) . '" style="width: ' . $this->getWidth() . '; min-width: ' . $this->getMinWidth() . '; height: ' . $this->getHeight() . ';">
				' . $html . '
			</div>
			';
        return $output;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\AbstractAjaxTemplate\Template\Elements\AbstractJqueryElement::generateJs()
     */
    public function generateJs()
    {
        foreach ($this->getWidget()->getButtons() as $btn){
            $button_js .= $this->getTemplate()->getElement($btn)->generateJs();   
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