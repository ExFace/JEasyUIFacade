/**
 * Exercises real IndexedDB in an isolated database without changing user preferences.
 */
export async function runEuiSetupStorageRegression() {
    const oManager = globalThis.exfSetupManager;
    const sOriginalDbName = oManager._databaseName;
    const sOriginalInstallation = oManager._sInstallationName;
    const sDbName = 'exf-eui-setups-test-' + crypto.randomUUID();
    const aKey = ['screen', 'table', 'object'];
    const oLegacySetup = {
        slug: aKey[0],
        widget_id: aKey[1],
        object_id: aKey[2],
        setup_uid: 'legacy',
        setup_uxon: '{"columns":[]}',
        setup_name: 'Existing setup',
        date_last_applied: '2026-01-01T00:00:00.000Z'
    };
    let iChecks = 0;
    const fnCheck = (bCondition, sMessage) => {
        if (!bCondition) {
            throw new Error(sMessage);
        }
        iChecks++;
    };
    const fnRead = () => oManager.dexie.getCurrentSetup(...aKey);
    const fnSave = sUid => oManager.dexie.saveLastAppliedSetup(...aKey, sUid, '{"columns":[]}', sUid);

    oManager._databaseName = sDbName;
    try {
        const oLegacyDb = new Dexie(sDbName);
        try {
            oLegacyDb.version(3).stores({setups: '[slug+widget_id+object_id], setup_uid, date_last_applied'});
            await oLegacyDb.setups.put(oLegacySetup);
        } finally {
            oLegacyDb.close();
        }

        oManager.init('dev');
        const oImported = await fnRead();
        fnCheck(oImported.setup_uid === 'legacy'
            && oImported.setup_name === oLegacySetup.setup_name
            && oImported.setup_uxon === oLegacySetup.setup_uxon
            && oImported.date_last_applied === oLegacySetup.date_last_applied,
            'First load must preserve the legacy setup and metadata.');
        await fnSave('dev-setup');
        oManager.init('prod');
        fnCheck((await fnRead()).setup_uid === 'legacy', 'Prod must inherit legacy preferences, not new dev preferences.');
        await fnSave('prod-setup');
        oManager.init('dev');
        fnCheck((await fnRead()).setup_uid === 'dev-setup', 'Saving in prod must not change dev.');
        fnCheck((await oManager.getSetupProperty(...aKey, null, 'setup_uxon')).columns.length === 0,
            'Auto-apply must resolve the scoped JSON payload.');
        for (let iPart = 0; iPart < aKey.length; iPart++) {
            const aOtherKey = [...aKey];
            aOtherKey[iPart] = 'other';
            fnCheck(await oManager.dexie.getCurrentSetup(...aOtherKey) === undefined,
                'Screen, widget and object identities must remain independent.');
        }

        await oManager.dexie.deleteCurrentSetup(...aKey);
        oManager.init('dev');
        fnCheck(await fnRead() === undefined, 'Reset must survive reload without re-importing legacy data.');
        oManager.init('prod');
        fnCheck((await fnRead()).setup_uid === 'prod-setup', 'Reset in dev must not clear prod.');
        oManager.init('reset-first');
        await oManager.dexie.deleteCurrentSetup(...aKey);
        fnCheck(await fnRead() === undefined, 'Reset before the first read must not resurrect legacy data.');
        oManager.init('save-first');
        await fnSave('new');
        fnCheck((await fnRead()).setup_uid === 'new', 'First save must override legacy data.');
        oManager.init('concurrent');
        await Promise.all([fnRead(), fnRead(), oManager.dexie.deleteCurrentSetup(...aKey)]);
        fnCheck(await fnRead() === undefined, 'Concurrent migration must not undo a reset.');

        oManager.init('pending-dev');
        const oDevSave = fnSave('pending-dev');
        oManager.init('pending-prod');
        const oProdSave = fnSave('pending-prod');
        await Promise.all([oDevSave, oProdSave]);
        oManager.init('pending-dev');
        fnCheck((await fnRead()).setup_uid === 'pending-dev', 'Pending operations must retain their installation identity.');

        const oDb = oManager._openDatabase();
        try {
            fnCheck((await oDb.setups.get(aKey)).setup_uid === 'legacy', 'Legacy data must remain unchanged.');
            fnCheck((await oDb.current_setups.get(['prod', ...aKey])).setup_uid === 'prod-setup',
                'Preferences must use the same compound key as UI5.');
            await oDb.current_setups.put({...oLegacySetup, installation: 'ui5', setup_uid: 'ui5-setup'});
            await oDb.installations.put({installation: 'ui5'});
        } finally {
            oDb.close();
        }
        oManager.init('ui5');
        fnCheck((await fnRead()).setup_uid === 'ui5-setup', 'Existing UI5 version 4 preferences must be preserved, not re-imported.');
        return {checks: iChecks, passed: true};
    } finally {
        oManager._databaseName = sOriginalDbName;
        oManager._sInstallationName = sOriginalInstallation;
        await Dexie.delete(sDbName);
    }
}
