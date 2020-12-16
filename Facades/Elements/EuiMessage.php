<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\HtmlMessageTrait;
use exface\Core\Widgets\MessageList;

class EuiMessage extends EuiText
{
    use HtmlMessageTrait;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiText::buildHtml()
     */
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