<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Interfaces\Actions\ActionInterface;

/**
 * Generates jEasyUI linkbutton controls for data actions.
 */
class EuiDataButton extends EuiButton
{
    /**
     * Keep comment action progress inside the feed; retain default progress for other data widgets.
     *
     * {@inheritDoc}
     * @see EuiAbstractElement::buildJsBusyIconShow()
     */
    public function buildJsBusyIconShow()
    {
        $inputElement = $this->getInputElement();
        return $inputElement ? $inputElement->buildJsBusyIconShow() : parent::buildJsBusyIconShow();
    }

    /**
     * Clear local comment progress without closing another widget's global progress UI.
     *
     * {@inheritDoc}
     * @see EuiAbstractElement::buildJsBusyIconHide()
     */
    public function buildJsBusyIconHide()
    {
        $inputElement = $this->getInputElement();
        return $inputElement ? $inputElement->buildJsBusyIconHide() : parent::buildJsBusyIconHide();
    }
}