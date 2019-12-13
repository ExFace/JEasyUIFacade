<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\WizardStep;
use exface\Core\Widgets\Toolbar;

/**
 *
 * @author Andrej Kabachnik
 *        
 * @method Wizard getWidget()
 *        
 */
class EuiWizard extends EuiTabs
{
    public function buildHtml()
    {
        $tabsHtml = parent::buildHtml();
        $toolbarsHtml = '';
        foreach ($this->getStepToolbars() as $tb) {
            $tbHtml = $this->getFacade()->getElement($tb)->buildHtml();
            // Replace the exf-form-toolbar CSS class to be able to distinuish between step-toolbars
            // and regular form-toolbars
            $toolbarsHtml .= str_replace('exf-form-toolbar', 'exf-wizard-step-toolbar', $tbHtml);
        }
        foreach ($this->getWidget()->getToolbars() as $tb) {
            $toolbarsHtml .= $this->getFacade()->getElement($tb)->buildHtml();
        }
        return <<<HTML

    <div class="easyui-panel exf-wizard-wrapper" data-options="fit: {$this->getFitOption()}, footer: '#{$this->getIdToolbar()}'">
        {$tabsHtml}
    </div>
    <div id="{$this->getIdToolbar()}" class="exf-wizard-buttons">
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
    
    public function buildJs()
    {
        $js = parent::buildJs();
        
        $tbJSON = [];
        foreach ($this->getStepToolbars() as $tb) {
            $tbJSON[] = $this->getFacade()->getElement($tb)->getId();
        }
        $tbJSONString = json_encode($tbJSON, JSON_PRETTY_PRINT);
        
        return $js . <<<JS
        
    function {$this->buildJsFunctionPrefix()}switchStep(iStepIdx) {
        var aToolbarIds = $tbJSONString;
        var iToolbarCnt = aToolbarIds.length;
        var jqTabs = $('#{$this->getId()}');
        aToolbarIds.forEach(function(sId, iIdx){
            var jqTb = $('#' + sId);
            jqTb.hide();
            if (iIdx == iStepIdx) jqTb.show();
            if (iIdx > iStepIdx) {
                jqTabs.{$this->getElementType()}('disableTab', iIdx);
            } else {
                jqTabs.{$this->getElementType()}('enableTab', iIdx);
            }
        });
        
    }
        
JS;
    }
    
    public function buildJsDataOptions()
    {
        return parent::buildJsDataOptions() . ", onSelect: function(title,index){ {$this->buildJsFunctionPrefix()}switchStep(index); }";
    }
}