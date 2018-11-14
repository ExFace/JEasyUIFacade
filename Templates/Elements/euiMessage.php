<?php
namespace exface\JEasyUiTemplate\Templates\Elements;

use exface\Core\Templates\AbstractAjaxTemplate\Elements\HtmlMessageTrait;

class euiMessage extends euiText
{
    use HtmlMessageTrait;
    
    public function buildHtml()
    {
        return $this->buildHtmlMessage();
    }
}
?>