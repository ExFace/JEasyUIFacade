<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\WizardButton;
use exface\Core\Interfaces\Actions\iResetWidgets;

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
     * A WizardButton validates it's step, performs it's action and navigates to another step:
     * 
     * 1) validate the button's wizard step first if we are going to leave it
     * 2) perform the regular button's action
     * 3) navigate to the target wizard step
     * 
     * Note, that the action JS will perform step validation in any case - even if the
     * button does not navigate to another step.
     * 
     * {@inheritdoc}
     * @see EuiButton::buildJsClickFunction()
     */
    public function buildJsClickFunction()
    {
        $widget = $this->getWidget();
        $tabsElement = $this->getFacade()->getElement($widget->getWizardStep()->getParent());
        
        if ($widget->getResetInput() === true && ($widget->hasAction() === false || $widget->getAction() instanceof iResetWidgets)) {
            return $this->buildJsInputReset($widget, $this->getFacade()->getElement($widget->getWizardStep()));
        }
        
        $goToStepJs = '';
        $validateJs = '';
        if (($nextStep = $widget->getGoToStepIndex()) !== null) {
            $stepElement = $this->getFacade()->getElement($widget->getWizardStep());
            if ($widget->getValidateCurrentStep() === true) {
                $validateJs = <<<JS
            
                    if({$stepElement->buildJsValidator()} === false) {
                        {$stepElement->buildJsValidationError()}
                        return;
                    }
                    
JS;
            }
            $goToStepJs = <<<JS

                    jqTabs.{$tabsElement->getElementType()}('select', $nextStep);
                    {$tabsElement->buildJsFunctionPrefix()}switchStep($nextStep, true);

JS;
            
        }
        
        // If the button has an action, the step navigation should only happen once
        // the action is complete!
        $this->addOnSuccessScript($goToStepJs);
        $actionJs = parent::buildJsClickFunction();
        if ($actionJs) {
            $goToStepJs = '';
        }
        
        return <<<JS
        
					var jqTabs = $('#{$tabsElement->getId()}');
                    {$validateJs}
                    {$actionJs}
                    {$goToStepJs}
                    
JS;
    }
}