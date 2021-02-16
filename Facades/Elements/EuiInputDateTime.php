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
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInputDate::getElementType()
     */
    public function getElementType()
    {
        return 'datetimebox';
    }
}