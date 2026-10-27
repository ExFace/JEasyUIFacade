$(async function() {
    var assertions = [];
    window.datetimeRegression = {assertions: assertions, passed: false};
    function check(condition, message) {
        if (!condition) {
            $('#results').text('FAIL: ' + message);
            throw new Error(message);
        }
        assertions.push(message);
    }
    function editor(grid, index) {
        return $(grid.datagrid('getEditor', {index: index, field: 'date'}).target);
    }
    function query(input, text) {
        input.datetimebox('setText', text);
        input.datetimebox('options').keyHandler.query.call(input[0], text);
    }
    function close(input) {
        input.datetimebox('options').onHidePanel.call(input[0]);
    }

    var rows = [
        {date: null},
        {date: ''},
        {},
        {date: '2026-10-27 14:35:12'},
        {date: '28.10.2026 09:10:11'}
    ];
    var snapshot = JSON.stringify(rows);
    var grid = $('<table>').appendTo('body').datagrid({
        width: 400,
        height: 240,
        columns: [[{field: 'date', width: 350, editor: {type: 'datetimebox', options: datetimeOptions()}}]],
        onLoadSuccess: function() {
            for (var i = 0; i < $(this).datagrid('getRows').length; i++) {
                $(this).datagrid('beginEdit', i);
            }
        },
        data: rows
    });
    for (var i = 0; i < rows.length; i++) {
        check(grid.datagrid('getEditors', i).length === 1, 'Row ' + i + ' initializes its editor');
    }
    check(JSON.stringify(grid.datagrid('getRows')) === snapshot, 'Initialization preserves initial row data');
    check(grid.datagrid('getChanges').length === 0, 'Initialization produces no changes');
    for (var i = 0; i < 3; i++) {
        var empty = editor(grid, i);
        check(empty.datetimebox('getText') === '' && empty.data('_internalValue') === '', 'Nullable row ' + i + ' stays empty');
        check(empty.datetimebox('isValid'), 'Nullable row ' + i + ' is valid');
        close(empty);
        empty.datetimebox('options').keyHandler.enter.call(empty[0], {});
        check(empty.datetimebox('getValue') === '', 'Closing/enter does not commit a placeholder in row ' + i);
    }
    var valid = editor(grid, 3);
    check(valid.datetimebox('getText') === '27.10.2026 14:35:12', 'Internal datetime formats with its time intact');
    check(valid.data('_internalValue') === '2026-10-27 14:35:12', 'Valid datetime round-trips internally');
    check(valid.datetimebox('isValid'), 'Valid datetime passes editor-local validation');
    for (var i = 0; i < rows.length; i++) {
        grid.datagrid('endEdit', i);
    }
    check(JSON.stringify(grid.datagrid('getRows')) === snapshot, 'Ending unchanged edits preserves null, missing and formatted values');
    check(grid.datagrid('getChanges').length === 0, 'Ending unchanged edits produces no changes');

    grid.datagrid('beginEdit', 0);
    var input = editor(grid, 0);
    query(input, 'not a datetime');
    check(!input.datetimebox('isValid'), 'Invalid typed text fails validation');
    close(input);
    input.datetimebox('options').keyHandler.enter.call(input[0], {});
    check(input.datetimebox('getText') === 'not a datetime', 'Closing/enter preserves invalid text');
    check(input.data('_internalValue') === '', 'Invalid input never acquires an internal date');
    grid.datagrid('endEdit', 0);
    check(grid.datagrid('getEditors', 0).length === 1, 'Invalid row cannot be committed');
    input.datetimebox('setValue', 'still invalid');
    check(input.datetimebox('getText') === 'still invalid' && !input.datetimebox('isValid'), 'Native assignment preserves invalid input and validation');
    input.datetimebox('setValue', '2026-10-29 16:17:18');
    check(input.data('_internalValue') === '2026-10-29 16:17:18', 'Native assignment accepts valid datetime after invalid input');
    input.datetimebox('options').keyHandler.enter.call(input[0], {});
    check(input.data('_internalValue') === '2026-10-29 16:17:18', 'Enter preserves a valid date and time');
    grid.datagrid('endEdit', 0);
    check(grid.datagrid('getChanges').length === 1, 'A real edit is tracked');
    check(grid.datagrid('getRows')[0].date === '29.10.2026 16:17:18', 'A real edit retains the native editor output format');

    var standalone = $('<input>').appendTo('body').datetimebox($.extend(datetimeOptions(), {value: 'invalid initial value'}));
    check(standalone.datetimebox('getText') === 'invalid initial value' && !standalone.datetimebox('isValid'), 'Standalone initialization preserves invalid text');
    standalone.datetimebox('showPanel').datetimebox('hidePanel');
    check(standalone.datetimebox('getText') === 'invalid initial value', 'Opening/closing the real panel preserves invalid text');
    standalone.datetimebox('setValue', '2026-10-27 14:35:12');
    standalone.datetimebox('showPanel').datetimebox('hidePanel');
    check(standalone.data('_internalValue') === '2026-10-27 14:35:12', 'Opening/closing the real panel preserves a valid time');
    var changes = 0;
    standalone.on('change', function() { changes++; });
    clearDatetime(standalone);
    check(standalone.datetimebox('getText') === '' && changes === 1, 'Facade setter clears and retains its change event');
    close(standalone);
    check(standalone.datetimebox('getText') === '', 'Closing after clearing does not restore the previous date');
    standalone.datetimebox('showPanel').datetimebox('hidePanel');
    check(standalone.datetimebox('getValue') === '' && standalone.datetimebox('getDate') === null, 'Opening/closing the real panel keeps an empty datetime null');
    standalone.datetimebox({required: true});
    check(!standalone.datetimebox('isValid'), 'Required empty datetime still fails validation');
    standalone.datetimebox({required: false});
    standalone.datetimebox('setValue', '');
    var selected = new Date(2026, 9, 30, 11, 12, 13);
    standalone.datetimebox('calendar').calendar('moveTo', selected);
    standalone.datetimebox('options').onSelect.call(standalone[0], selected);
    close(standalone);
    check(standalone.data('_internalValue') === '2026-10-30 11:12:13', 'Explicit calendar selection replaces an empty placeholder');
    standalone.datetimebox('setText', 'invalid before query');
    close(standalone);
    check(standalone.datetimebox('getText') === 'invalid before query', 'Closing before the delayed query preserves invalid text');

    var native = $('<input>').appendTo('body').datetimebox();
    $.fn.datagrid.defaults.editors.datetimebox.setValue(native, '');
    check($.fn.datagrid.defaults.editors.datetimebox.getValue(native) === '', 'Non-facade datetime editors keep native behavior');

    function batchGrid(data, extraOptions, container) {
        var callback = batchedGridOptions().onLoadSuccess;
        var pending;
        var table = $('<table>').appendTo(container || 'body').datagrid($.extend({
            width: 800,
            height: 240,
            columns: [[
                {field: 'a', width: 100, editor: 'textbox'},
                {field: 'b', width: 100, editor: 'textbox'},
                {field: 'c', width: 100, editor: 'textbox'},
                {field: 'date', width: 180, editor: {type: 'datetimebox', options: datetimeOptions()}},
                {field: 'end', width: 180, editor: {type: 'datetimebox', options: datetimeOptions()}},
                {field: 'comment', width: 100, editor: 'textbox'}
            ]],
            data: data,
            onLoadSuccess: function(loaded) {
                pending = callback.call(this, loaded);
            }
        }, extraOptions));
        return {table: table, ready: function() { return pending; }};
    }
    function batchRows(count) {
        return Array.from({length: count}, function(_, index) {
            return {a: String(index), b: 'text', c: 'status', date: index % 2 ? '2026-10-27 14:35:12' : null, end: '', comment: ''};
        });
    }

    var pagedContainer = $('<div>').css({width: 800, height: 360}).appendTo('body');
    var pagedRowsStayedHidden = true;
    var paged = batchGrid({total: 397, rows: batchRows(40)}, {
        fit: true,
        pagination: true,
        pageSize: 40,
        pageList: [40, 80],
        onBeginEdit: function() {
            pagedRowsStayedHidden = pagedRowsStayedHidden
                && $(this).datagrid('getPanel').find('.datagrid-view2').css('visibility') === 'hidden';
        }
    }, pagedContainer);
    paged.table.datagrid('resize');
    var pagedBody = paged.table.datagrid('getPanel').find('.datagrid-view2 .datagrid-body');
    check(pagedBody.height() > 0, 'Resizing during initialization preserves the paginated row area');
    check(pagedBody.css('visibility') === 'hidden', 'Intermediate row rendering is hidden without collapsing its layout');
    check(paged.table.datagrid('getPager').css('visibility') === 'hidden', 'The pager is hidden until the page is ready');
    check(paged.table.datagrid('getPanel').find('.datagrid-mask-msg').css('visibility') === 'visible', 'The loading indicator stays visible while rows are hidden');
    var loadingMessage = paged.table.datagrid('getPanel').find('.datagrid-mask-msg')[0];
    var messageBounds = loadingMessage.getBoundingClientRect();
    var overlayBounds = loadingMessage.parentElement.getBoundingClientRect();
    var textRange = document.createRange();
    textRange.selectNodeContents(loadingMessage);
    var textBounds = textRange.getBoundingClientRect();
    check(textBounds.top > messageBounds.top && textBounds.bottom < messageBounds.bottom
        && textBounds.left > messageBounds.left && textBounds.right < messageBounds.right,
        'The loading text and progress fit inside the message borders');
    check(Math.abs((messageBounds.left + messageBounds.right) - (overlayBounds.left + overlayBounds.right)) < 2
        && Math.abs((messageBounds.top + messageBounds.bottom) - (overlayBounds.top + overlayBounds.bottom)) < 2,
        'The loading message is centered horizontally and vertically');
    await paged.ready();
    check(pagedRowsStayedHidden, 'Rows remain hidden throughout every initialization batch');
    check(pagedBody.css('visibility') === 'visible'
        && paged.table.datagrid('getPager').css('visibility') === 'visible', 'The completed table and pager are revealed together');
    check(pagedBody.height() > 0, 'Paginated rows remain visible after initialization');
    check(paged.table.datagrid('getEditors', 39).length === 6, 'The last row of the first page initializes every editor');
    check(paged.table.datagrid('getPager').pagination('options').total === 397, 'Editor initialization preserves the pagination total');
    var pagedHeight = pagedBody.height();
    paged.table.datagrid('resize');
    check(pagedBody.height() === pagedHeight, 'Removing the initialization overlay does not change the fitted row area');
    check(paged.table.datagrid('getChanges').length === 0, 'Paginated initialization leaves the initial data unchanged');

    var batchData = batchRows(32);
    var batchSnapshot = JSON.stringify(batchData);
    var ticks = 0;
    var rowsPerTurn = {};
    var heartbeat = setInterval(function() { ticks++; }, 0);
    var batchStart = performance.now();
    var batched = batchGrid(batchData, {
        onBeginEdit: function() {
            rowsPerTurn[ticks] = (rowsPerTurn[ticks] || 0) + 1;
        }
    });
    var completed = 0;
    var changed = 0;
    batched.table.on('exf-editors-ready', function() { completed++; });
    batched.table.on('exf-batch-change', function() { changed++; });
    check(completed === 0, 'Dependent setup waits for batched initialization');
    check(batched.table.datagrid('getPanel').find('.datagrid-mask').length === 1, 'Initialization has a loading mask');
    check(batched.table.datagrid('getPanel').prop('inert'), 'Incomplete editors cannot receive keyboard or pointer input');
    check(batched.table.datagrid('getPanel').find('.datagrid-mask-msg').text().includes('0 / 32'), 'The loading message reports row initialization progress');
    try {
        await batched.ready();
    } finally {
        clearInterval(heartbeat);
    }
    check(ticks > 1, 'The browser runs other tasks between initialization batches');
    check(Math.max.apply(null, Object.values(rowsPerTurn)) <= 5, 'Each batch initializes at most five rows');
    check(completed === 1, 'Dependent setup runs once after all editors exist');
    for (var i = 0; i < batchData.length; i++) {
        check(batched.table.datagrid('getEditors', i).length === 6, 'Batched row ' + i + ' has every editor');
    }
    check(batched.table.datagrid('getPanel').find('.datagrid-mask').length === 0, 'The initialization mask is removed');
    check(!batched.table.datagrid('getPanel').prop('inert'), 'Completed editors become interactive');
    check(JSON.stringify(batched.table.datagrid('getRows')) === batchSnapshot, 'Batched initialization preserves every original value');
    editor(batched.table, batchData.length - 1).trigger('change');
    check(changed === 1, 'Deferred change tracking includes the last batch');
    for (var i = 0; i < batchData.length; i++) {
        batched.table.datagrid('endEdit', i);
    }
    check(batched.table.datagrid('getChanges').length === 0, 'Batched unchanged rows are not marked as edited');
    check(JSON.stringify(batched.table.datagrid('getRows')) === batchSnapshot, 'Ending batched edits retains original nullable and valid values');
    window.datetimeRegression.batching = {rows: batchData.length, heartbeatTicks: ticks, elapsedMs: Math.round(performance.now() - batchStart)};

    var datetimeEditor = $.fn.datagrid.defaults.editors.datetimebox;
    var nativeResize = datetimeEditor.resize;
    var resizeCalls = 0;
    datetimeEditor.resize = function(target, width) {
        resizeCalls++;
        return nativeResize.call(this, target, width);
    };
    try {
        var optimized = batchGrid(batchRows(8));
        await optimized.ready();
        check(resizeCalls === 16, 'Each datetime editor is sized once instead of once per subsequent row');
        optimized.table.datagrid('fixColumnSize');
        check(resizeCalls === 32, 'Normal editor resizing is restored after initialization');
        check(optimized.table.datagrid('options').editors === $.fn.datagrid.defaults.editors, 'Initialization restores the original editor registry');
    } finally {
        datetimeEditor.resize = nativeResize;
    }

    var customResizeCalls = 0;
    var customEditors = $.extend({}, $.fn.datagrid.defaults.editors, {
        datetimebox: $.extend({}, datetimeEditor, {
            resize: function(target, width) {
                customResizeCalls++;
                return nativeResize.call(this, target, width);
            }
        })
    });
    var custom = batchGrid(batchRows(3), {editors: customEditors});
    await custom.ready();
    check(customResizeCalls === 12, 'Custom editor resize callbacks retain their original native invocation count');
    check(custom.table.datagrid('options').editors === customEditors, 'Initialization preserves a custom editor registry');

    var reloaded = false;
    var staleEdits = 0;
    var replacement = batchRows(3);
    var reload = batchGrid(batchRows(12), {
        onBeginEdit: function() {
            if (!reloaded) {
                staleEdits++;
                if (staleEdits === 1) {
                    var target = $(this);
                    setTimeout(function() {
                        reloaded = true;
                        target.datagrid('loadData', replacement);
                    }, 0);
                }
            }
        }
    });
    var reloadCompleted = 0;
    reload.table.on('exf-editors-ready', function() { reloadCompleted++; });
    await reload.ready();
    check(reload.table.datagrid('getPanel').find('.datagrid-mask').length === 1
        && reload.table.datagrid('getPanel').prop('inert'), 'Stale cancellation leaves the replacement load protected');
    check(reload.table.datagrid('getPanel').hasClass('exf-edit-initializing'), 'Stale cancellation does not reveal the partially initialized replacement');
    await reload.ready();
    check(reloaded && staleEdits < 12, 'Reload cancels stale batches before all old rows initialize');
    check(reloadCompleted === 1, 'Cancelled loads do not run deferred setup');
    check(reload.table.datagrid('getEditors', 2).length === 6, 'The replacement load initializes all of its rows');
    check(reload.table.datagrid('getPanel').find('.datagrid-mask').length === 0, 'Reload removes only completed initialization masks');
    check(!reload.table.datagrid('getPanel').prop('inert'), 'Replacement initialization restores interaction');
    check(!reload.table.datagrid('getPanel').hasClass('exf-edit-initializing'), 'Replacement initialization reveals the completed table');

    var sameRows = batchRows(3);
    var same = batchGrid(sameRows);
    var sameCompleted = 0;
    same.table.on('exf-editors-ready', function() { sameCompleted++; });
    var oldReady = same.ready();
    same.table.datagrid('loadData', sameRows);
    await Promise.all([oldReady, same.ready()]);
    check(sameCompleted === 1, 'Reloading the same row array also cancels the previous initialization');

    var filtered = batchGrid(batchRows(4), {onLoadSuccess: function() {}});
    await jeasyui_datagrid_begin_edit_all(filtered.table, function(row) { return Number(row.a) % 2 === 0; });
    check(filtered.table.datagrid('getEditors', 0).length === 6
        && filtered.table.datagrid('getEditors', 2).length === 6, 'The matrix predicate initializes every eligible row');
    check(filtered.table.datagrid('getEditors', 1).length === 0
        && filtered.table.datagrid('getEditors', 3).length === 0, 'The matrix predicate preserves intentionally noneditable rows');

    var emptyBatch = batchGrid([]);
    var emptyCompleted = 0;
    emptyBatch.table.on('exf-editors-ready', function() { emptyCompleted++; });
    await emptyBatch.ready();
    check(emptyCompleted === 1 && !emptyBatch.table.datagrid('getPanel').prop('inert'), 'Empty grids finish setup without leaving an initialization lock');

    var destroyScheduled = false;
    var destroyed = batchGrid(batchRows(12), {
        onBeginEdit: function() {
            if (!destroyScheduled) {
                destroyScheduled = true;
                var target = $(this);
                setTimeout(function() { target.datagrid('getPanel').panel('destroy'); }, 0);
            }
        }
    });
    var destroyedCompleted = 0;
    destroyed.table.on('exf-editors-ready', function() { destroyedCompleted++; });
    await destroyed.ready();
    check(!destroyed.table.data('datagrid') && destroyedCompleted === 0, 'Destroyed grids cancel batches and deferred setup');

    var expectedError = new Error('Expected editor failure');
    var failing = batchGrid(batchRows(2), {
        onBeforeEdit: function(index) {
            if (index === 1) {
                throw expectedError;
            }
        }
    });
    var failure;
    try {
        await failing.ready();
    } catch (error) {
        failure = error;
    }
    check(failure === expectedError, 'Editor failures propagate rather than being suppressed');
    check(failing.table.datagrid('getPanel').find('.datagrid-mask').length === 0, 'Editor failures release the initialization mask');
    check(!failing.table.datagrid('getPanel').prop('inert'), 'Editor failures restore the previous interactive state');
    check(!failing.table.datagrid('getPanel').hasClass('exf-edit-initializing'), 'Editor failures do not leave the table hidden');
    check(failing.table.datagrid('options').editors === $.fn.datagrid.defaults.editors, 'Editor failures also restore the original editor registry');

    window.datetimeRegression.passed = true;
    $('#results').text('PASS: ' + assertions.length + ' assertions');
});
