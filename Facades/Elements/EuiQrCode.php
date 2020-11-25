<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryQrCodeTrait;

/**
 * @method \exface\Core\Widgets\QrCode getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class EuiQrCode extends EuiDisplay
{
    use JqueryQrCodeTrait;
    
    public function buildHtml()
    {
        return $this->buildHtmlLabelWrapper($this->buildHtmlQrCode());
    }
}