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
    public function getElementType() : ?string
    {
        return 'datetimebox';
    }

    protected function buildJsDataOptions()
    {
        return '...jeasyui_datetimebox_options({' . parent::buildJsDataOptions() . '}, '
            . $this->escapeString($this->translate('MESSAGE.INVALID.INPUTDATE')) . ')';
    }

    /**
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInputDate::buildJsValueSetterMethod()
     */
    public function buildJsValueSetterMethod($value)
    {
        return "each(function() {
            var jqSelf = $(this);
            var mValue = {$value};
            if (mValue == null || mValue === '') {
                jqSelf.datetimebox('clear');
            } else {
                jqSelf.datetimebox('setValue', mValue);
            }
        }).trigger('change')";
    }
}
