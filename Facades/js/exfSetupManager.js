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
                manager.quickSelect.refresh(setupsTableId);
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
                manager.quickSelect.setupsLoaded(setupsTableId);
                if (entry && entry.setup_name) {
                    var options = manager.quickSelect._findBySetupsTable(setupsTableId);
                    if (options) {
                        manager.quickSelect._setCaption(options, entry.setup_name);
                    }
                }
            });
        },

        quickSelect: {
            _configs: {},

            register: function (options) {
                manager.quickSelect._configs[options.tableId] = options;
                options.loaded = false;
                options.loading = false;
                options.initializeAttempts = 0;
                setTimeout(function () {
                    manager.quickSelect.initialize(options.tableId);
                }, 0);
                manager.dexie.getCurrentSetup(options.slug, options.widgetId, options.objectId).then(function (entry) {
                    manager.quickSelect._setCaption(options, entry && entry.setup_name ? entry.setup_name : options.defaultCaption);
                });
            },

            initialize: function (tableId) {
                var options = manager.quickSelect._configs[tableId];
                var table;
                var panel;
                var title;
                var captionText;
                var captionButton;
                var configuratorButton;
                var showCaptionMenu;

                if (!options) {
                    return;
                }
                table = $('#' + tableId);
                if (table.length === 0 || table.data('datagrid') === undefined) {
                    if (options.initializeAttempts < 20) {
                        options.initializeAttempts += 1;
                        setTimeout(function () {
                            manager.quickSelect.initialize(tableId);
                        }, 50);
                    }
                    return;
                }
                panel = table.datagrid('getPanel');
                title = panel.panel('header').find('.panel-title');
                if (title.length && $('#' + options.captionButtonId).length === 0) {
                    captionText = $('<span></span>')
                        .attr('id', options.captionButtonId + '_text')
                        .text(options.defaultCaption);
                    captionButton = $('<a href="javascript:void(0)"></a>')
                        .attr('id', options.captionButtonId)
                        .attr('aria-haspopup', 'true')
                        .addClass('exf-setup-quickselect-caption')
                        .append($('<span></span>').addClass('fa fa-caret-down'));
                    title.empty().append(captionText).append(captionButton);
                    manager.quickSelect._createMenu(options, options.captionMenuId);
                    showCaptionMenu = function () {
                        var button = captionButton;
                        var offset = button.offset();

                        manager.quickSelect.load(options.setupsTableId);
                        $('#' + options.captionMenuId).menu('show', {
                            left: offset.left,
                            top: offset.top + button.outerHeight()
                        });
                    };
                    captionButton.on('mouseenter', showCaptionMenu);
                    captionButton.on('click', function (event) {
                        event.preventDefault();
                        event.stopPropagation();
                        showCaptionMenu();
                    });
                }
                configuratorButton = $('#' + options.configuratorButtonId);
                if (configuratorButton.length && configuratorButton.data('splitbutton') === undefined) {
                    manager.quickSelect._createMenu(options, options.configuratorMenuId);
                    configuratorButton.removeClass('easyui-linkbutton').addClass('easyui-splitbutton').splitbutton({
                        menu: '#' + options.configuratorMenuId
                    });
                }
                manager.quickSelect.refresh(options.setupsTableId);
            },

            load: function (setupsTableId) {
                var options = manager.quickSelect._findBySetupsTable(setupsTableId);
                var table = $('#' + setupsTableId);

                if (!options || options.loaded || options.loading || table.length === 0 || table.data('datagrid') === undefined) {
                    return;
                }
                options.loading = true;
                table.datagrid('reload');
            },

            setupsLoaded: function (setupsTableId) {
                var options = manager.quickSelect._findBySetupsTable(setupsTableId);

                if (options) {
                    options.loaded = true;
                    options.loading = false;
                }
                manager.quickSelect.refresh(setupsTableId);
            },

            refresh: function (setupsTableId) {
                var options = manager.quickSelect._findBySetupsTable(setupsTableId);
                var table = $('#' + setupsTableId);
                var rows = table.length && table.data('datagrid') !== undefined ? table.datagrid('getRows') || [] : [];
                var activeRow = null;

                if (!options) {
                    return;
                }
                rows.forEach(function (row) {
                    if (row.SETUP_APPLIED) {
                        activeRow = row;
                    }
                });
                manager.quickSelect._setCaption(options, activeRow ? activeRow.NAME : options.defaultCaption);
                manager.quickSelect._renderMenu(options, options.captionMenuId, rows);
                manager.quickSelect._renderMenu(options, options.configuratorMenuId, rows);
            },

            _findBySetupsTable: function (setupsTableId) {
                var result = null;

                Object.keys(manager.quickSelect._configs).some(function (tableId) {
                    var options = manager.quickSelect._configs[tableId];
                    if (options.setupsTableId === setupsTableId) {
                        result = options;
                        return true;
                    }
                    return false;
                });
                return result;
            },

            selectSetupRow: function (setupsTableId, setupUid) {
                var table = $('#' + setupsTableId);
                var rows;
                var rowIndex = -1;

                if (table.length === 0 || table.data('datagrid') === undefined) {
                    return false;
                }
                rows = table.datagrid('getRows') || [];
                rows.some(function (row, index) {
                    if (row.UID === setupUid) {
                        rowIndex = index;
                        return true;
                    }
                    return false;
                });
                if (rowIndex < 0) {
                    return false;
                }
                table.datagrid('clearSelections').datagrid('selectRow', rowIndex);
                return true;
            },

            _createMenu: function (options, menuId) {
                var menu = $('#' + menuId);

                if (menu.length === 0) {
                    menu = $('<div></div>').attr('id', menuId).addClass('exf-setup-quickselect-menu').appendTo('body');
                }
                menu.menu({
                    onShow: function () {
                        manager.quickSelect.load(options.setupsTableId);
                    }
                });
            },

            _renderMenu: function (options, menuId, rows) {
                var menu = $('#' + menuId);

                if (menu.length === 0 || menu.data('menu') === undefined) {
                    return;
                }
                menu.children('.menu-item').each(function () {
                    menu.menu('removeItem', this);
                });
                menu.children('.menu-sep').remove();
                if (menu.children('.menu-line').length === 0) {
                    menu.prepend($('<div></div>').addClass('menu-line'));
                }
                if (!options.loaded) {
                    menu.menu('appendItem', {text: options.loadingCaption, disabled: true});
                } else if (rows.length === 0) {
                    menu.menu('appendItem', {text: options.emptyCaption, disabled: true});
                } else {
                    rows.forEach(function (row) {
                        var favorite = String(row.WIDGET_SETUP_USER__FAVORITE_FLAG) === '1';
                        var active = !!row.SETUP_APPLIED;
                        var items;
                        var item;

                        menu.menu('appendItem', {
                            text: manager.quickSelect._escapeHtml(manager.quickSelect._decodeHtml(row.NAME || '')),
                            iconCls: active ? 'fa fa-check' : (favorite ? 'fa fa-star' : ''),
                            onclick: function () {
                                options.apply(row);
                            }
                        });
                        item = menu.children('.menu-item').last();
                        item
                            .attr('title', manager.quickSelect._decodeHtml(row.DESCRIPTION || ''))
                            .toggleClass('exf-setup-active', active)
                            .toggleClass('exf-setup-favorite', favorite);
                        items = [
                            {text: options.applyCaption, iconCls: 'fa fa-check', onclick: options.apply},
                            {text: options.updateCaption, iconCls: 'fa fa-refresh', onclick: options.update},
                            {text: options.editCaption, iconCls: 'fa fa-pencil', onclick: options.edit}
                        ];
                        items.forEach(function (action) {
                            if (typeof action.onclick !== 'function') {
                                return;
                            }
                            menu.menu('appendItem', {
                                parent: item[0],
                                text: action.text,
                                iconCls: action.iconCls,
                                onclick: function () {
                                    action.onclick(row);
                                }
                            });
                        });
                    });
                }
                menu.menu('appendItem', {separator: true});
                menu.menu('appendItem', {
                    text: options.clearCaption,
                    iconCls: options.clearIconCls,
                    onclick: options.clear
                });
                menu.menu('appendItem', {
                    text: options.openCaption,
                    iconCls: 'fa fa-cog',
                    onclick: options.openConfigurator
                });
                menu.menu('appendItem', {
                    text: options.saveCaption,
                    iconCls: 'fa fa-bookmark-o',
                    onclick: options.save
                });
                if (menu.is(':visible')) {
                    var offset = menu.offset();
                    menu.menu('show', {left: offset.left, top: offset.top});
                }
            },

            _setCaption: function (options, caption) {
                var captionText = $('#' + options.captionButtonId + '_text');

                if (captionText.length) {
                    captionText.text(manager.quickSelect._decodeHtml(caption || options.defaultCaption));
                }
            },

            _decodeHtml: function (value) {
                return $('<div></div>').html(value === null || value === undefined ? '' : String(value)).text();
            },

            _escapeHtml: function (value) {
                return $('<div></div>').text(value === null || value === undefined ? '' : String(value)).html();
            }
        },

        datatable: {
            getConfiguration: function (tableId, sorterBuilderId, conditionBuilderId) {
                var table = $('#' + tableId);
                var options = table.datagrid('options');
                var fields = table.datagrid('getColumnFields', true).concat(table.datagrid('getColumnFields'));
                var sorterBuilder = $('#' + sorterBuilderId);
                var conditionBuilder = $('#' + conditionBuilderId);
                var searchModel = conditionBuilder.data('exfConditionBuilder') !== undefined
                    ? conditionBuilder.exfConditionBuilder('getConditionGroup')
                    : null;
                var configuration = {
                    columns: [],
                    sorters: [],
                    advanced_conditions: searchModel
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
                    conditionBuilder.exfConditionBuilder('setModel', configuration.advanced_conditions || null);
                }
                table.datagrid('resize');
            }
        }
    };

    return manager;
}));
