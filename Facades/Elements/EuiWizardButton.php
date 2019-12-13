<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\WizardButton;

/**
 *
 * @author Andrej Kabachnik
 *        
 * @method WizardButton getWidget()
 *        
 */
class EuiWizardButton extends EuiButton
{
    /**
     * {@inheritdoc}
     * @see EuiButton::buildJsClickFunction()
     */
    public function buildJsClickFunction()
    {
        $widget = $this->getWidget();
        $tabsElement = $this->getFacade()->getElement($widget->getWizardStep()->getParent());
        $goToStepJs = '';
        if (($nextStep = $widget->getGoToStep()) !== null) {
            $stepElement = $this->getFacade()->getElement($widget->getWizardStep());
            $goToStepJs = <<<JS

                    if({$stepElement->buildJsValidator()} === false) {
                        {$stepElement->buildJsValidationError()}
                        return;
                    }
                    jqTabs.{$tabsElement->getElementType()}('select', $nextStep);

JS;
            
        }
        $js = <<<JS

					var jqTabs = $('#{$tabsElement->getId()}');
                    {$goToStepJs}

JS;
        return $js . parent::buildJsClickFunction();
    }
}