<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\Form;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryToolbarsTrait;
use exface\Core\DataTypes\WidgetVisibilityDataType;

/**
 * The Form widget is just another panel in jEasyUI.
 * The HTML form cannot be used here, because form widgets can contain
 * tabs and the tabs implementation in jEasyUI is using HTML forms, so it does not work within a <form> element.
 *
 * @method Form getWidget()
 *        
 * @author Andrej Kabachnik
 *        
 */
class EuiForm extends EuiPanel
{
    
    use JqueryToolbarsTrait;

    public function buildHtml()
    {
        return parent::buildHtml() . $this->buildHtmlFooter();
    }
    
    public function buildCssElementClass()
    {
        return parent::buildCssElementClass() . ' exf-form';
    }

    protected function buildHtmlFooter()
    {
        $output = '';
        if ($this->getWidget()->hasButtons()) {
            $output = <<<HTML

				<div id="{$this->getFooterId()}" class="exf-form-footer">
					{$this->buildHtmlToolbars()}
				</div>

HTML;
        }
        return $output;
    }

    protected function hasFooter()
    {
        if ($this->getWidget()->hasButtons()) {
            return true;
        }
        return false;
    }

    protected function getFooterId()
    {
        return $this->getId() . '_footer';
    }

    public function buildJsDataOptions()
    {
        $options = parent::buildJsDataOptions();
        
        if ($this->hasFooter()) {
            $options .= ", footer: '#" . $this->getFooterId() . "'";
        }
        
        return $options;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiWidgetGrid::buildJs()
     */
    public function buildJs()
    {
        return parent::buildJs() . $this->buildJsSubmitOnEnter();
    }
    
    /**
     * Returns JS code to trigger the action of the default button of the form on enter
     * 
     * The default button is
     * - the only promoted button if there is only one promoted button
     * - the only button with normal visibility if there are no promoted buttons and only one normal one
     * 
     * @return string
     */
    protected function buildJsSubmitOnEnter()
    {
        $promotedButtons = [];
        $regularButtons = [];
        foreach ($this->getWidget()->getButtons() as $btn) {
            if ($btn->getVisibility() == WidgetVisibilityDataType::PROMOTED) {
                $promotedButtons[] = $btn;
            }
            if ($btn->getVisibility() == WidgetVisibilityDataType::NORMAL) {
                $regularButtons[] = $btn;
            }
        }
        
        $defaultBtn = null;
        if (count($promotedButtons) === 1) {
            $defaultBtn = $promotedButtons[0];
        } elseif (empty($promotedButtons) && count($regularButtons) === 1) {
            $defaultBtn = $regularButtons[0];
        }
        
        if ($defaultBtn === null) {
            return '';
        }
        
        // Use keyup() instead of keypress() because the latter did not work with jEasyUI combos.
        return <<<JS
        setTimeout(function(){
            $('#{$this->getId()}').find('input').keyup(function (ev) {
                var keycode = (ev.keyCode ? ev.keyCode : ev.which);
                if (keycode == '13') {
                    {$this->getFacade()->getElement($defaultBtn)->buildJsClickFunctionName()}();
                }
            })
        }, 10)
        
JS;
    }
}