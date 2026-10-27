$(async function() {
    var assertions = [];
    window.inputSelectRegression = {assertions: assertions, passed: false};
    function check(condition, message) {
        if (!condition) {
            $('#results').text('FAIL: ' + message);
            throw new Error(message);
        }
        assertions.push(message);
    }

    var selectRows = [
        {number: 101, string: 'DONE'},
        {number: 0, string: 'OPEN'},
        {number: null},
        {number: 10, string: ''}
    ];
    var selectSnapshot = JSON.stringify(selectRows);
    var selects = $('<table>').appendTo('body').datagrid({
        width: 600,
        height: 240,
        columns: [[
            {field: 'number', width: 280, editor: {type: 'combobox', options: numberSelectOptions()}},
            {field: 'string', width: 280, editor: {type: 'combobox', options: stringSelectOptions()}}
        ]],
        data: selectRows
    });
    await jeasyui_datagrid_begin_edit_all(selects);
    function selectEditor(index, field) {
        return $(selects.datagrid('getEditor', {index: index, field: field}).target);
    }
    var numberSelect = selectEditor(0, 'number');
    var stringSelect = selectEditor(0, 'string');
    check(numberSelect.combobox('getText') === 'Completed', 'Numeric enum editors display labels instead of stored keys');
    check(selectEditor(1, 'number').combobox('getText') === 'Not started', 'Numeric zero resolves to its enum label');
    check(stringSelect.combobox('getText') === 'Review "ready"', 'String enum editors display labels with quotes intact');
    check(numberSelect.combobox('getValue') === '101'
        && stringSelect.combobox('getValue') === 'DONE', 'Enum labels retain their original selection keys');
    numberSelect.next('.combo').find('.combo-arrow').trigger('click');
    var numberPanel = numberSelect.combobox('panel');
    check(numberPanel.is(':visible') && numberPanel.height() > 0, 'Clicking the numeric enum arrow opens a nonempty selection menu');
    check(numberPanel.find('.combobox-item').length === 4
        && numberPanel.text().includes('In progress'), 'The numeric menu includes enum labels and the empty selection');
    numberSelect.combobox('hidePanel');
    stringSelect.next('.combo').find('.combo-arrow').trigger('click');
    check(stringSelect.combobox('panel').is(':visible')
        && stringSelect.combobox('panel').text().includes('Review "ready"'), 'Clicking the string enum arrow opens its labeled options');
    stringSelect.combobox('hidePanel');
    for (var i = 0; i < selectRows.length; i++) {
        selects.datagrid('endEdit', i);
    }
    check(JSON.stringify(selects.datagrid('getRows')) === selectSnapshot, 'Untouched selects preserve numeric, null, missing and empty keys');
    check(selects.datagrid('getChanges').length === 0, 'Opening enum editors does not mark untouched rows as changed');
    selects.datagrid('beginEdit', 0);
    numberSelect = selectEditor(0, 'number');
    numberSelect.next('.combo').find('.combo-arrow').trigger('click');
    numberSelect.combobox('panel').find('.combobox-item').filter(function() {
        return $(this).text() === 'In progress';
    }).trigger('click');
    check(numberSelect.combobox('getValue') === '10'
        && numberSelect.combobox('getText') === 'In progress', 'Selecting a numeric label changes the stored key, not the label');
    selects.datagrid('endEdit', 0);
    check(selects.datagrid('getChanges').length === 1
        && selects.datagrid('getRows')[0].number === '10', 'A real enum selection is tracked with its key');

    var standaloneSelect = $('<select><option value="OPEN">Open</option><option value="DONE" selected>Review</option></select>')
        .appendTo('body').combobox(stringSelectOptions());
    check(standaloneSelect.combobox('getValue') === 'DONE'
        && standaloneSelect.combobox('getText') === 'Review "ready"', 'Standalone selects preserve their selected HTML value and enum label');
    var multipleSelect = $('<input>').appendTo('body').combobox(multipleSelectOptions());
    multipleSelect.combobox('setValues', ['OPEN', 'DONE']);
    check(multipleSelect.combobox('getValues').join(',') === 'OPEN,DONE'
        && multipleSelect.combobox('getText') === 'Open,Review "ready"', 'Multiselects preserve selection keys and display both labels');
    var plainSelect = $('<input>').appendTo('body').combobox({data: [{value: '1', text: 'One'}]});
    $.fn.datagrid.defaults.editors.combobox.setValue(plainSelect, 1);
    check($.fn.datagrid.defaults.editors.combobox.getValue(plainSelect) === '1', 'Non-facade combobox editors retain native value behavior');

    window.inputSelectRegression.passed = true;
    $('#results').text('PASS: ' + assertions.length + ' assertions');
});
