<?php
namespace exface\JEasyUiTemplate\Templates\JEasyUiTemplate\Elements;

/**
 * Generates a jEasyUI datetimebox for InputDateTime widgets.
 * 
 * @author Andrej Kabachnik
 *
 */
class euiInputDateTime extends euiInputDate
{
    protected function init()
    {
        parent::init();
        $this->setElementType('datetimebox');
    }
}