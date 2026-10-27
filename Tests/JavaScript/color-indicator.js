$(function() {
    var assertions = [];
    window.colorIndicatorRegression = {assertions: assertions, passed: false};
    function check(condition, message) {
        if (!condition) {
            $('#results').text('FAIL: ' + message);
            throw new Error(message);
        }
        assertions.push(message);
    }

    var longLabel = 'Archäologische Maßnahme mit Abschlusstätigkeit (1)';
    var grid = $('<table>').appendTo('body').datagrid({
        width: 540,
        height: 320,
        nowrap: false,
        autoRowHeight: true,
        frozenColumns: [[{field: 'id', width: 40}]],
        columns: [[
            {field: 'label', width: 240, fixed: true, formatter: colorLabelFormatter},
            {field: 'color', width: 50, formatter: colorSwatchFormatter},
            {field: 'input', width: 150, editor: 'textbox'}
        ]],
        data: [
            {id: 1, label: longLabel, color: '#a85a00', input: ''},
            {id: 2, label: 'Kurze Maßnahme', color: '#a85a00', input: ''},
            {id: 3, label: longLabel + ' ' + longLabel + ' ' + longLabel, color: '#a85a00', input: ''}
        ]
    });
    for (var index = 0; index < 3; index++) {
        grid.datagrid('beginEdit', index);
    }

    function checkSpacing(stage) {
        var panel = grid.datagrid('getPanel');
        panel.find('.datagrid-view2 tr.datagrid-row').each(function(index) {
            var row = this.getBoundingClientRect();
            $(this).find('.exf-colorindicator').each(function() {
                var indicator = this.getBoundingClientRect();
                check(indicator.top - row.top >= 3 && row.bottom - indicator.bottom >= 3,
                    stage + ': row ' + index + ' keeps space above and below its color indicators');
            });
            var frozenRow = panel.find('.datagrid-view1 tr.datagrid-row').eq(index)[0].getBoundingClientRect();
            check(frozenRow.height === row.height, stage + ': row ' + index + ' stays aligned with frozen columns');
        });
    }
    checkSpacing('Initial layout');
    var labels = grid.datagrid('getPanel').find('.datagrid-view2 td[field="label"] .exf-colorindicator');
    check(labels.eq(0).height() > labels.eq(1).height(), 'Long labels wrap instead of being clipped to one line');
    check(labels.eq(2).height() > labels.eq(0).height(), 'The row grows to accommodate longer wrapped labels');
    check(labels.eq(0).css('background-color') === 'rgb(168, 90, 0)', 'The inset preserves the configured background color');
    grid.datagrid('getColumnOption', 'label').width = 150;
    grid.datagrid('fixColumnSize', 'label');
    grid.datagrid('fixRowHeight');
    checkSpacing('Narrower column');

    var contrastHost = $('<div>').css('color', 'rgb(64, 64, 64)').appendTo('body');
    [
        ['#000000', 'rgb(255, 255, 255)'],
        ['#ffffff', 'rgb(0, 0, 0)'],
        ['#a85a00', 'rgb(255, 255, 255)'],
        ['navy', 'rgb(255, 255, 255)'],
        ['gold', 'rgb(0, 0, 0)'],
        ['rgb(20, 40, 60)', 'rgb(255, 255, 255)'],
        ['rgba(20, 40, 60, 1)', 'rgb(255, 255, 255)']
    ].forEach(function(test) {
        var indicator = $(colorLabelFormatter('Readable text', {color: test[0]})).appendTo(contrastHost);
        check(indicator.css('color') === test[1], 'The foreground contrasts with background ' + test[0]);
        var foreground = exfColorTools._cssColorToRgba(indicator.css('color'));
        var background = exfColorTools._cssColorToRgba(indicator.css('background-color'));
        var ratio = exfColorTools._contrastRatio(exfColorTools._relativeLuminance(foreground),
            exfColorTools._relativeLuminance(background));
        check(ratio >= 4.5, 'Text on ' + test[0] + ' meets the 4.5:1 contrast threshold');
    });
    var transparent = $(colorLabelFormatter('No fill', {color: 'transparent'})).appendTo(contrastHost);
    check(transparent.css('color') === 'rgb(64, 64, 64)', 'Transparent indicators retain the inherited text color');
    var textOnly = $(colorTextFormatter('Colored text', {color: 'navy'})).appendTo(contrastHost);
    check(textOnly.css('color') === 'rgb(0, 0, 128)', 'Non-filled indicators keep their configured text color');
    var swatch = $(colorSwatchFormatter('navy', {color: 'navy'})).appendTo(contrastHost);
    check(swatch[0].style.color === '', 'Color-only swatches do not override the text color');
    var staticLabel = $(colorLabelHtml).appendTo(contrastHost);
    check(staticLabel.css('color') === 'rgb(255, 255, 255)', 'Static indicator markup also uses a contrasting foreground');
    contrastHost.remove();
    grid.datagrid('updateRow', {index: 1, row: {color: '#000000'}});
    var updated = grid.datagrid('getPanel').find('.datagrid-view2 td[field="label"] .exf-colorindicator').eq(1);
    check(updated.css('background-color') === 'rgb(0, 0, 0)' && updated.css('color') === 'rgb(255, 255, 255)',
        'Refreshing a row recomputes the foreground from its changed background');

    window.colorIndicatorRegression.passed = true;
    $('#results').text('PASS: ' + assertions.length + ' assertions');
});
