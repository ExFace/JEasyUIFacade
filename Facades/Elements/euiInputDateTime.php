<?php
namespace exface\JEasyUIFacade\Facades\Elements;

/**
 * Generates a jEasyUI datetimebox for InputDateTime widgets.
 * 
 * @author Andrej Kabachnik
 *
 */
class EuiInputDateTime extends EuiInputDate
{
    protected function init()
    {
        parent::init();
        $this->setElementType('datetimebox');
    }
}