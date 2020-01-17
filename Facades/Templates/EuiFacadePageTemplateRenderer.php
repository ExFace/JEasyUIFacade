<?php
namespace exface\JEasyUIFacade\Facades\Templates;

use exface\Core\Facades\AbstractAjaxFacade\Templates\FacadePageTemplateRenderer;

class EuiFacadePageTemplateRenderer extends FacadePageTemplateRenderer
{
    protected function renderPlaceholderValue(string $placeholder) : string
    {
        if ($placeholder === 'breadcrumbs') {
            $string = parent::renderPlaceholderValue('~widget:NavCrumbs');
            $string = preg_replace("/\r|\n/", "", $string);
            return str_replace(["'",'"'], "\'", $string);
        }
        
        return parent::renderPlaceholderValue($placeholder);
    }
}