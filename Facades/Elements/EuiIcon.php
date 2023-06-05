<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\HtmlIconTrait;

/**
 * @method \exface\Core\Widgets\Icon getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class EuiIcon extends EuiDisplay
{
    use HtmlIconTrait;
    
    public function buildJs()
    {
        return parent::buildJs()
        . $this->buildJsValueSetter($this->escapeString($this->getWidget()->getValueWithDefaults(), true, true)) . ';';
    }
    
}