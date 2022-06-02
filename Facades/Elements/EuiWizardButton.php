<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\WizardButton;
use exface\Core\Interfaces\Actions\iResetWidgets;
use exface\Core\Interfaces\Actions\ActionInterface;

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
    public function buildJsClickFunction(ActionInterface $action = null, string $jsRequestData = null) : string
    {
        $widget = $this->getWidget();
        $action = $action ?? $this->getAction();
        $tabsElement = $this->getFacade()->getElement($widget->getWizardStep()->getParent());
        
        if (empty($widget->getResetWidgetIds()) === false && ($widget->hasAction() === false || $action instanceof iResetWidgets)) {
            return $this->buildJsResetWidgets();
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
        $actionJs = parent::buildJsClickFunction($action, $jsRequestData);
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