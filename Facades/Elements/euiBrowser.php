<?php
namespace exface\JEasyUIFacade\Facades\Elements;
use exface\Core\Facades\AbstractAjaxFacade\Elements\HtmlBrowserTrait;
class EuiBrowser extends EuiAbstractElement
{
    use HtmlBrowserTrait;
    
    public function buildCssElementStyle()
    {
        return 'width: 100%; height: calc(100% - 3px); border: 0;';
    }
}