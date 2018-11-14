<?php
namespace exface\JEasyUiTemplate\Templates\Elements;

use exface\Core\Templates\AbstractAjaxTemplate\Elements\HtmlMessageTrait;
use exface\Core\Widgets\Message;
use exface\Core\Widgets\MessageList;

class euiMessage extends euiText
{
    use HtmlMessageTrait;
    
    public function buildHtml()
    {
        if ($this->getWidget()->getParent() instanceof MessageList) {
            return $this->buildHtmlMessage();
        } else {
            return $this->buildHtmlGridItemWrapper($this->buildHtmlMessage());
        }
    }
}
?>