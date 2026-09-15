<?php
namespace exface\JEasyUIFacade\Facades\Elements\Traits;

use exface\Core\Widgets\DataTable;
use exface\Core\Interfaces\Widgets\iSupportWidgetSetups;
use exface\JEasyUIFacade\Facades\Elements\EuiDataConfigurator;

/**
 * Adds DataTable widget setup functions without exposing them to other EuiData elements.
 *
 * @author Andrej Kabachnik
 */
trait EuiDataTableSetupTrait
{
    /**
     * Builds a setup-specific widget function, or returns NULL for other functions.
     *
     * @param string|null $functionName
     * @param array $parameters
     * @param string|null $jsRequestData
     * @return string|null
     */
    protected function buildJsCallFunctionForSetup(string $functionName = null, array $parameters = [], ?string $jsRequestData = null) : ?string
    {
        $configurator = $this->getWidget()->getConfiguratorWidget();
        if (! $configurator instanceof iSupportWidgetSetups || ! $configurator->hasSetups()) {
            return null;
        }

        /** @var EuiDataConfigurator $configuratorElement */
        $configuratorElement = $this->getFacade()->getElement($configurator);
        $requestDataJs = $jsRequestData ?? 'null';
        $parametersJs = json_encode($parameters ?? []);
        $dataWidget = $this->getWidget();
        $screenSlugJs = $this->escapeString($dataWidget->getUiScreen()->getUrlSlug());
        $widgetIdJs = $this->escapeString($dataWidget->getIdInScreen());
        $objectIdJs = $this->escapeString($dataWidget->getMetaObject()->getId());
        $setupsTableIdJs = $this->escapeString($this->getFacade()->getElement($configurator->getSetupsTab()->getWidgetFirst())->getId());

        switch ($functionName) {
            case DataTable::FUNCTION_DUMP_SETUP:
                $userUidJs = $this->escapeString($this->getWorkbench()->getSecurity()->getAuthenticatedUser()->getUid());
                return <<<JS
(function(){
    var aParams = {$parametersJs};
    var oRequestData = {$requestDataJs};
    var oSetup;
    var bAutoApply;
    if (!Array.isArray(aParams) || aParams.length < 6 || !oRequestData) {
        console.warn('dump_setup() called with invalid parameters or request data:', aParams);
        return;
    }
    aParams = aParams.map(function(parameter){ return typeof parameter === 'string' ? parameter.trim() : parameter; });
    bAutoApply = aParams[6] === true || aParams[6] === 'true';
    oRequestData.rows = oRequestData.rows || [];
    oRequestData.rows[0] = oRequestData.rows[0] || {};
    oSetup = exfSetupManager.datatable.getConfiguration(
        '{$this->getId()}',
        '{$configuratorElement->getIdOfSorterBuilder()}',
        '{$configuratorElement->getIdOfConditionBuilder()}'
    );
    if (oRequestData.rows[0][aParams[0]] === undefined) {
        oRequestData.rows[0][aParams[5]] = {$userUidJs};
    }
    oRequestData.rows[0][aParams[0]] = JSON.stringify(oSetup);
    oRequestData.rows[0][aParams[1]] = {$screenSlugJs};
    oRequestData.rows[0][aParams[2]] = {$widgetIdJs};
    oRequestData.rows[0][aParams[3]] = 'exface/core/Mutations/Prototypes/DataTableSetup.php';
    oRequestData.rows[0][aParams[4]] = {$objectIdJs};
    if (bAutoApply) {
        {$this->buildJsCallFunctionForSetup(DataTable::FUNCTION_APPLY_SETUP, ['[#' . ($parameters[0] ?? 'SETUP_UXON') . '#]'], $requestDataJs)}
    }
})();
JS;

            case DataTable::FUNCTION_APPLY_SETUP:
                return <<<JS
(function(){
    var aParams = {$parametersJs};
    var oRequestData = {$requestDataJs};
    var oPassedSetup = null;
    var bLocal = aParams[0] === 'localStorage';
    if (!bLocal) {
        if (!oRequestData || !oRequestData.rows || !oRequestData.rows[0]) {
            return;
        }
        var oMatch = String(aParams[0] || '').match(/\[#(.*?)#\]/);
        if (!oMatch || !oRequestData.rows[0][oMatch[1]]) {
            return;
        }
        oPassedSetup = JSON.parse(oRequestData.rows[0][oMatch[1]]);
    }
    exfSetupManager.getSetupProperty({$screenSlugJs}, {$widgetIdJs}, {$objectIdJs}, oPassedSetup, 'setup_uxon').then(function(oSetup){
        if (!oSetup) {
            return;
        }
        exfSetupManager.datatable.applyConfiguration(
            '{$this->getId()}',
            '{$configuratorElement->getIdOfSorterBuilder()}',
            '{$configuratorElement->getIdOfConditionBuilder()}',
            oSetup
        );
        {$this->buildJsHeaderFiltersSet($configuratorElement->buildJsSearchConditionsGetter())}
        if (!bLocal) {
            exfSetupManager.dexie.saveLastAppliedSetup(
                oRequestData.rows[0].SLUG,
                oRequestData.rows[0].WIDGET_ID,
                oRequestData.rows[0].OBJECT,
                oRequestData.rows[0].UID,
                oRequestData.rows[0].SETUP_UXON,
                oRequestData.rows[0].NAME
            ).then(function(){
                exfSetupManager.markCurrentSetupAsActive(
                    {$setupsTableIdJs},
                    {$screenSlugJs},
                    {$widgetIdJs},
                    {$objectIdJs},
                    true
                );
            });
        }
        {$this->buildJsHeaderSortersReset()}
        {$this->buildJsRefresh()};
    });
})();
JS;

            case DataTable::FUNCTION_CLEAR_APPLIED_SETUP:
                return <<<JS
(function(){
    var oRequestData = {$requestDataJs};
    if (!oRequestData || !oRequestData.rows || !oRequestData.rows[0]) {
        return;
    }
    exfSetupManager.dexie.getCurrentSetup({$screenSlugJs}, {$widgetIdJs}, {$objectIdJs}).then(function(oEntry){
        if (oEntry && oEntry.setup_uid === oRequestData.rows[0].UID) {
            exfSetupManager.dexie.deleteCurrentSetup({$screenSlugJs}, {$widgetIdJs}, {$objectIdJs});
            {$this->buildJsResetter()}
        }
    });
})();
JS;
        }

        return null;
    }

    /**
     * Auto-applies the setup remembered for this table on the current device.
     *
     * @return string
     */
    protected function buildJsSetupAutoApply() : string
    {
        $configurator = $this->getWidget()->getConfiguratorWidget();
        if (! $configurator instanceof iSupportWidgetSetups || ! $configurator->hasSetups()) {
            return '';
        }

        $screenSlugJs = $this->escapeString($this->getWidget()->getUiScreen()->getUrlSlug());
        $widgetIdJs = $this->escapeString($this->getWidget()->getIdInScreen());
        $objectIdJs = $this->escapeString($this->getWidget()->getMetaObject()->getId());

        return <<<JS
exfSetupManager.dexie.getCurrentSetup({$screenSlugJs}, {$widgetIdJs}, {$objectIdJs}).then(function(oEntry){
    if (oEntry) {
        {$this->buildJsCallFunctionForSetup(DataTable::FUNCTION_APPLY_SETUP, ['localStorage'])}
    }
});
JS;
    }
}
