<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\Form;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryToolbarsTrait;

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
}
?>