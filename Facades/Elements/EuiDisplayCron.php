<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JsCronstrueTrait;

/**
 * @method \exface\Core\Widgets\DisplayCron getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class EuiDisplayCron extends EuiDisplay
{
    use JsCronstrueTrait;
    
    public function buildJs()
    {
        return parent::buildJs() . <<<JS

            {$this->buildJsValueSetter($this->buildJsValueDecorator($this->buildJsValueGetter()), true)};

JS;
    }
    
}