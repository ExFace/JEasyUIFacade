<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\Card;

/**
 * 
 *
 * @method Card getWidget()
 *        
 * @author Andrej Kabachnik
 *        
 */
class EuiCard extends EuiForm
{
    public function buildCssElementClass()
    {
        return 'exf-card';
    }
}