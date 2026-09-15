;(function (global, factory) {
    if (typeof exports === 'object' && typeof module !== 'undefined') {
        module.exports = factory();
    } else if (typeof define === 'function' && define.amd) {
        define(factory);
    } else {
        global.exfSetupManager = factory();
    }
}(this, function () {
    'use strict';

    var manager = {
        _databaseName: 'exf-ui5-widgets',

        _openDatabase: function () {
            var database = new Dexie(manager._databaseName);
            database.version(1).stores({
                setups: '[page_id+widget_id], setup_uid, date_last_applied'
            });
            database.version(2).stores({
                setups: null
            });
            database.version(3).stores({
                setups: '[slug+widget_id+object_id], setup_uid, date_last_applied'
            });
            return database;
        },

        _isDexieAvailable: function () {
            if (typeof Dexie === 'undefined') {
                console.warn('Dexie.js not available, cannot manage setups in IndexedDB.');
                return false;
            }
            return true;
        },

        getSetupProperty: function (slug, widgetId, objectId, passedData, key) {
            if (passedData !== null && passedData !== undefined) {
                return Promise.resolve(passedData);
            }
            return manager.dexie.getCurrentSetup(slug, widgetId, objectId).then(function (entry) {
                if (!entry || entry[key] === undefined || entry[key] === null) {
                    return null;
                }
                return key === 'setup_uxon' ? JSON.parse(entry[key]) : entry[key];
            }).catch(function (error) {
                console.error('Error reading widget setup from IndexedDB:', error);
                return null;
            });
        },

        dexie: {
            getCurrentSetup: function (slug, widgetId, objectId) {
                if (!manager._isDexieAvailable()) {
                    return Promise.resolve(null);
                }
                var database = manager._openDatabase();
                return database.setups.get([slug, widgetId, objectId]).catch(function (error) {
                    console.error('Error reading widget setup from IndexedDB:', error);
                    return null;
                }).then(function (entry) {
                    database.close();
                    return entry;
                }, function (error) {
                    database.close();
                    throw error;
                });
            },

            deleteCurrentSetup: function (slug, widgetId, objectId) {
                if (!manager._isDexieAvailable()) {
                    return Promise.resolve();
                }
                var database = manager._openDatabase();
                return database.setups.delete([slug, widgetId, objectId]).then(function () {
                    database.close();
                }, function (error) {
                    database.close();
                    console.error('Error deleting widget setup from IndexedDB:', error);
                });
            },

            saveLastAppliedSetup: function (slug, widgetId, objectId, setupUid, setupUxon, setupName) {
                if (!manager._isDexieAvailable()) {
                    return Promise.resolve();
                }
                var database = manager._openDatabase();
                return database.setups.put({
                    slug: slug,
                    widget_id: widgetId,
                    object_id: objectId,
                    setup_uid: setupUid,
                    setup_uxon: setupUxon,
                    setup_name: setupName,
                    date_last_applied: new Date().toISOString()
                }).then(function () {
                    database.close();
                }, function (error) {
                    database.close();
                    console.error('Error saving widget setup to IndexedDB:', error);
                });
            }
        },

        markCurrentSetupAsActive: function (setupsTableId, slug, widgetId, objectId, refreshIfMissing) {
            var table = $('#' + setupsTableId);

            if (table.length === 0 || table.data('datagrid') === undefined) {
                return Promise.resolve();
            }
            return manager.dexie.getCurrentSetup(slug, widgetId, objectId).then(function (entry) {
                var setupUid = entry ? entry.setup_uid : null;
                var rows = table.datagrid('getRows') || [];
                var hasActiveSetup = setupUid === null;

                rows.forEach(function (row, index) {
                    var marker = row.UID === setupUid ? 'check' : '';
                    hasActiveSetup = hasActiveSetup || row.UID === setupUid;
                    if (row.SETUP_APPLIED !== marker) {
                        table.datagrid('updateRow', {
                            index: index,
                            row: {SETUP_APPLIED: marker}
                        });
                    }
                });
                if (refreshIfMissing === true && !hasActiveSetup) {
                    table.datagrid('reload');
                }
            });
        },

        datatable: {
            getConfiguration: function (tableId, sorterBuilderId, conditionBuilderId) {
                var table = $('#' + tableId);
                var options = table.datagrid('options');
                var fields = table.datagrid('getColumnFields', true).concat(table.datagrid('getColumnFields'));
                var sorterBuilder = $('#' + sorterBuilderId);
                var conditionBuilder = $('#' + conditionBuilderId);
                var searchModel = conditionBuilder.data('exfConditionBuilder') !== undefined
                    ? conditionBuilder.exfConditionBuilder('getModel')
                    : null;
                var configuration = {
                    columns: [],
                    sorters: [],
                    advanced_search: []
                };

                fields.forEach(function (field) {
                    var column = table.datagrid('getColumnOption', field);
                    if (column && field !== 'ck') {
                        configuration.columns.push({
                            column_name: field,
                            show: column.hidden !== true
                        });
                    }
                });
                if (sorterBuilder.data('exfSorterBuilder') !== undefined) {
                    configuration.sorters = sorterBuilder.exfSorterBuilder('getSorters').map(function (sorter) {
                        return {
                            attribute_alias: sorter.attribute_alias,
                            direction: String(sorter.direction).toUpperCase() === 'DESC' ? 'Descending' : 'Ascending'
                        };
                    });
                } else if (options.sortName) {
                    String(options.sortName).split(',').forEach(function (field, index) {
                        var column = table.datagrid('getColumnOption', field);
                        var directions = String(options.sortOrder || '').split(',');
                        configuration.sorters.push({
                            attribute_alias: column && column._attributeAlias ? column._attributeAlias : field,
                            direction: String(directions[index] || 'asc').toLowerCase() === 'desc' ? 'Descending' : 'Ascending'
                        });
                    });
                }
                if (searchModel && searchModel.operator === 'AND' && Array.isArray(searchModel.conditions)) {
                    searchModel.conditions.forEach(function (condition) {
                        if (condition.expression && condition.value !== '' && condition.value !== null && condition.value !== undefined) {
                            configuration.advanced_search.push({
                                attribute_alias: condition.expression,
                                comparator: condition.comparator,
                                value: condition.value,
                                exclude: condition.exclude === true
                            });
                        }
                    });
                }
                return configuration;
            },

            applyConfiguration: function (tableId, sorterBuilderId, conditionBuilderId, configuration) {
                var table = $('#' + tableId);
                var sorterBuilder = $('#' + sorterBuilderId);
                var conditionBuilder = $('#' + conditionBuilderId);

                if (!configuration) {
                    return;
                }
                if (Array.isArray(configuration.columns)) {
                    configuration.columns.forEach(function (setupColumn) {
                        var fields = table.datagrid('getColumnFields', true).concat(table.datagrid('getColumnFields'));
                        var field = setupColumn.column_name;
                        if (!field && setupColumn.attribute_alias) {
                            fields.some(function (candidate) {
                                var column = table.datagrid('getColumnOption', candidate);
                                if (column && column._attributeAlias === setupColumn.attribute_alias) {
                                    field = candidate;
                                    return true;
                                }
                                return false;
                            });
                        }
                        if (field && table.datagrid('getColumnOption', field)) {
                            table.datagrid(setupColumn.show === false ? 'hideColumn' : 'showColumn', field);
                        }
                    });
                }
                if (Array.isArray(configuration.sorters) && sorterBuilder.data('exfSorterBuilder') !== undefined) {
                    sorterBuilder.exfSorterBuilder('setSorters', configuration.sorters.map(function (sorter) {
                        return {
                            attribute_alias: sorter.attribute_alias,
                            direction: String(sorter.direction).toLowerCase().indexOf('desc') === 0 ? 'DESC' : 'ASC'
                        };
                    }));
                }
                if (conditionBuilder.data('exfConditionBuilder') !== undefined) {
                    conditionBuilder.exfConditionBuilder('setModel', {
                        operator: 'AND',
                        ignore_empty_values: true,
                        conditions: (Array.isArray(configuration.advanced_search) ? configuration.advanced_search : []).map(function (condition) {
                            return {
                                expression: condition.attribute_alias,
                                comparator: condition.comparator,
                                value: condition.value,
                                exclude: condition.exclude === true
                            };
                        }),
                        nested_groups: []
                    });
                }
                table.datagrid('resize');
            }
        }
    };

    return manager;
}));
