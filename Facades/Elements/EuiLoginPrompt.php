<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\Tabs;
use exface\Core\DataTypes\BooleanDataType;
use exface\Core\Exceptions\Facades\FacadeRuntimeError;
use exface\Core\Interfaces\Widgets\iLayoutWidgets;
use exface\Core\Widgets\Form;

/**
 *
 * @author Andrej Kabachnik
 *        
 * @method Tabs getWidget()
 *        
 */
class EuiLoginPrompt extends EuiContainer
{

    private $fit_option = false;

    private $style_as_pills = false;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::getElementType()
     */
    public function getElementType() : ?string
    {
        return 'tabs';
    }

    public function buildHtml()
    {
        $widget = $this->getWidget();
        
        $style = '';
        if ($this->isStandalone()) {
            $style .= " width: {$this->getWidthRelativeUnit()}px";
        }
        
        switch ($widget->getVisibility()) {
            case EXF_WIDGET_VISIBILITY_HIDDEN:
                $style .= ' visibility: hidden; height: 0px; padding: 0px;';
                break;
        }
        
        $headerHtml = '';
        if ($widget->hasMessages()) {
            foreach ($widget->getMessageList()->getMessages() as $message) {
                $messageEl = $this->getFacade()->getElement($message);
                $messageEl->addElementCssStyle($style);
                $headerHtml .= $messageEl->buildHtml();
            }
        }
        if ($this->isStandalone() && $caption = $this->getCaption()) {
            $headerHtml = '<h2 class="logo" style="float: none; text-align: center; margin: 0; padding: 0 10px 20px 10px">' . $caption . '</h2>' . $headerHtml;
        }
        
        $output = <<<HTML
    <div class="exf-loginprompt-wrapper">
        {$headerHtml}
        <div id="{$this->getId()}" style="{$style}" class="easyui-{$this->getElementType()} {$this->buildCssElementClass()}" data-options="{$this->buildJsDataOptions()}">
        	{$this->buildHtmlForWidgets()}
        </div>
    </div>

HTML;
        return $output;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiContainer::buildJs()
     */
    public function buildJs()
    {
        $js = parent::buildJs();
        
        // Give keyboard focus to the first visible input (e.g. the "User Name" field) of the
        // first login form once the prompt has rendered, so the user can start typing right away.
        // The selector mirrors EuiDialog: `.exf-input input` is the original (hidden) input that
        // easyui replaces, its next sibling holds the rendered textbox with the visible input.
        $forms = $this->getWidget()->getWidgets();
        $firstForm = $forms[0] ?? null;
        $autofocus = ! ($firstForm instanceof Form) || $firstForm->getAutofocusFirstInput() === true;
        if ($autofocus) {
            $js .= "\nsetTimeout(function(){ $('#{$this->getId()} .exf-input input').first().next().find('input').focus(); }, 100);";
        }
        
        return $js;
    }
    
    public function buildHtmlForWidgets()
    {
        $output = '';
        foreach ($this->getWidget()->getWidgets() as $subw) {
            $title = $subw->getCaption();
            $subw->setHideCaption(true);
            
            $output .= '<div title="' . $title . '">' . $this->getFacade()->getElement($subw)->buildHtml() . "</div>\n";
        }
        return $output;
    }

    /**
     * 
     * @return string
     */
    public function buildJsDataOptions()
    {     
        $border = $this->isStandalone() ? '' : "border:false,";
        return "$border tabPosition: 'top'";
    }

    protected function getFitOption() : bool
    {
        return $this->fit_option;
    }
    
    public function buildCssElementClass()
    {
        return 'exf-loginprompt exf-element';
    }
    
    protected function isStandalone() : bool
    {
        return $this->getWidget()->hasParent() === false;
    }
}