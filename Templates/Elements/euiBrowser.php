<?php
namespace exface\JEasyUiTemplate\Templates\Elements;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\HtmlBrowserTrait;
class euiBrowser extends euiAbstractElement
{
    use HtmlBrowserTrait;
    
    public function buildCssElementStyle()
    {
        return 'width: 100%; height: calc(100% - 3px); border: 0;';
    }
}