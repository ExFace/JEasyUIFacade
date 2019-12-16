<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\WizardStep;
use exface\Core\Widgets\Toolbar;

/**
 * Renders eui-tabs with some modifications to force the user to fillout each
 * tab step-by-step.
 * 
 * @author Andrej Kabachnik
 *
 * @method Wizard getWidget()
 *
 */
class EuiWizard extends EuiTabs
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiTabs::buildHtml()
     */
    public function buildHtml()
    {
        $facade = $this->getFacade();
        $widget = $this->getWidget();
        $tabsHtml = parent::buildHtml();
        $toolbarsHtml = '';
        
        // Add button groups from each step-form to the bottom toolbar. Make them aligned
        // right by default, while wizard-buttons (see below) will be aligned left.
        // NOTE: gathering entire toolbars does not work here as toolbars are 100%-blocks
        // to allow button groups to be positioned left and right within the toolbar. The
        // Wizard has a single merged toolbar where button groups of the steps are shown
        // or hidden automatically if their step is active/inactive.
        foreach ($widget->getSteps() as $step) {
            // Only add buttons from the main toolbar of the step. Theoretically steps
            // could have secondary toolbars - those should be rendered separately!
            $tb = $step->getToolbarMain();
            foreach ($tb->getButtonGroups() as $bgrp) {
                $bgrpEl = $facade->getElement($bgrp);
                
                // Make step-button-groups right-aligned by default
                $bgrpEl->setDefaultAlignment('right');
                // If the end up really right-aligned (i.e. no other alignment was provided 
                // explicitly), reverse the button order because otherwise the back-button
                // will get placed to the right of the next-button.
                if ($bgrpEl->buildCssTextAlignValue($bgrp->getAlign()) === 'right') {
                    $bgrp->reverseButtonOrder();
                }
                // Add a special class, that will be used to toggle the button group
                // visibility when navigating between steps.
                $bgrpEl->addElementCssClass($this->buildCssStepToolbarClass($tb));
                
                // Add the rendered button group to the toolbar contents
                $toolbarsHtml .= $bgrpEl->buildHtml();
            }
        }
        
        // Add the button groups from the wizard's own toolbars.
        foreach ($this->getWidget()->getToolbars() as $tb) {
            foreach ($tb->getButtonGroups() as $bgrp) {
                $toolbarsHtml .= $this->getFacade()->getElement($bgrp)->buildHtml();
            }
        }
        
        return <<<HTML
        
    <div class="easyui-panel exf-wizard-wrapper" data-options="fit: {$this->getFitOption()}, footer: '#{$this->getIdToolbar()}'">
        {$tabsHtml}
    </div>
    <div id="{$this->getIdToolbar()}" class="exf-toolbar exf-wizard-toolbar">
        {$toolbarsHtml}
    </div>
    
HTML;
    }
    
    /**
     *
     * @return Toolbar[]
     */
    protected function getStepToolbars() : array
    {
        $tbs = [];
        foreach ($this->getWidget()->getSteps() as $step) {
            foreach ($step->getToolbars() as $tb) {
                $tbs[] = $tb;
            }
        }
        return $tbs;
    }
    
    /**
     * Returns the id of the bottom toolbar
     * 
     * @return string
     */
    protected function getIdToolbar() : string
    {
        return $this->getId() . '_tooblar';
    }
    
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiTabs::getTabPosition()
     */
    protected function getTabPosition() : string
    {
        return $this->getTabPositionDefault();
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiTabs::getTabPositionDefault()
     */
    protected function getTabPositionDefault() : string
    {
        return 'top';
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiTabs::buildJsDataOptionHeaderWidth()
     */
    protected function buildJsDataOptionHeaderWidth() : string
    {
        return '';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiContainer::buildJs()
     */
    public function buildJs()
    {
        return parent::buildJs() . $this->buildJsFunctionSwitchStep();
    }
    
    /**
     * Creates a JS function to navigate to the given step index (iStepIdx).
     * 
     * @return string
     */
    protected function buildJsFunctionSwitchStep()
    {
        $tbJSON = [];
        foreach ($this->getStepToolbars() as $tb) {
            $tbJSON[] = $this->buildCssStepToolbarClass($tb);
        }
        $tbJSONString = json_encode($tbJSON, JSON_PRETTY_PRINT);
        
        return <<<JS
        
    function {$this->buildJsFunctionPrefix()}switchStep(iStepIdx) {
        var aToolbarIds = $tbJSONString;
        var iToolbarCnt = aToolbarIds.length;
        var jqTabs = $('#{$this->getId()}');
        aToolbarIds.forEach(function(sId, iIdx){console.log(sId);
            var jqBtnGroups = $('.' + sId);
            jqBtnGroups.hide();
            if (iIdx == iStepIdx) jqBtnGroups.show();
            if (iIdx > iStepIdx) {
                jqTabs.{$this->getElementType()}('disableTab', iIdx);
            } else {
                jqTabs.{$this->getElementType()}('enableTab', iIdx);
            }
        });
        
    }
    
JS;
    }
    
    /**
     * 
     * @param Toolbar $tb
     * @return string
     */
    protected function buildCssStepToolbarClass(Toolbar $tb) : string
    {
        return 'exf-step-toolbar-' . $tb->getId();
    }
    
    public function buildJsDataOptions()
    {
        return parent::buildJsDataOptions() . ", onSelect: function(title,index){ {$this->buildJsFunctionPrefix()}switchStep(index); }";
    }
}