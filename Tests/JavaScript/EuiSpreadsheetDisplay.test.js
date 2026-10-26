/* Run with the real JEasyUI and jSpreadsheet CSS in a browser. */
function runEuiSpreadsheetDisplayTests(sharedSource, euiSource) {
    var renderer = new Function('return ' + sharedSource.match(/return <<<'JS'\r?\n([\s\S]*?)\r?\nJS;/)[1])();
    var styler = new Function('return ' + euiSource.match(/protected function buildJsJExcelDisplayStyler[\s\S]*?return <<<'JS'\r?\n([\s\S]*?)\r?\nJS;/)[1])();
    var checks = 0;
    function check(name, actual, expected) {
        checks++;
        if (actual !== expected) throw new Error(name + ': expected ' + expected + ', got ' + actual);
    }
    function cssColor(value) {
        var node = document.createElement('span');
        node.style.backgroundColor = value;
        document.body.appendChild(node);
        var result = getComputedStyle(node).backgroundColor;
        node.remove();
        return result;
    }
    var host = document.createElement('div');
    host.className = 'exf-spreadsheet';
    host.innerHTML = '<table class="jexcel"><tbody><tr style="height:48px"><td class="readonly exf-spreadsheet-change" style="width:180px"></td><td>Editor</td></tr></tbody></table>';
    document.body.appendChild(host);
    var cell = host.querySelector('td');
    var editor = cell.nextElementSibling;
    cell.parentElement.style.height = '25px';
    var compactRowHeight = cell.getBoundingClientRect().height;
    cell.parentElement.style.height = '48px';
    var data = [[0, 'navy']];
    var display = {
        type: 'color', color: null, colorColumn: 'COLOR', colorScale: {}, colorRange: false,
        fill: true, colorOnly: false, hint: '', hintScale: {}, hintRange: false,
        format: function(value) { return value; }, style: styler
    };
    var widget = {
        _displayRenderer: renderer,
        _displaySemanticColors: {'~ERROR': 'orangered', '~WARNING': 'yellow', '~OK': 'lightgreen'},
        getColumnModel: function(index) { return index === 0 ? {display: display} : {}; },
        getColumnIndex: function(name) { return name === 'COLOR' ? 1 : -1; },
        getJExcel: function() { return {options: {data: data}}; }
    };
    function render(value) {
        renderer.renderDisplayCell.call(widget, cell, 0, 0, value);
        return cell.firstElementChild;
    }
    try {
        var node = render('Dynamic label');
        check('dynamic cell background', getComputedStyle(cell).backgroundColor, cssColor('navy'));
        check('dark dynamic background gets white text', getComputedStyle(node).color, cssColor('white'));
        check('compact outer padding', getComputedStyle(cell).padding, '2px 4px');
        check('datagrid label padding', getComputedStyle(node).padding, '0px 3px');
        check('display minimum height', getComputedStyle(node).minHeight, '18px');
        check('datagrid line height', getComputedStyle(node).lineHeight, '18px');
        check('readonly display row separator', getComputedStyle(cell).borderBottom, '1px solid ' + cssColor('#dfdfdf'));
        check('tall row stays tall', cell.getBoundingClientRect().height >= 48, true);
        check('editable cell keeps padding', getComputedStyle(editor).paddingTop, '4px');
        check('editable cell keeps border', getComputedStyle(editor).borderBottomColor, cssColor('#666'));
        check('change marker retained', cell.classList.contains('exf-spreadsheet-change'), true);
        data[0][1] = 'orange';
        render('Updated label');
        check('changed dynamic background', getComputedStyle(cell).backgroundColor, cssColor('orange'));
        check('light dynamic background gets black text', getComputedStyle(cell.firstElementChild).color, cssColor('black'));
        display.colorOnly = true;
        display.fill = false;
        node = render('Hidden label');
        check('color only hides visible text', node.textContent.trim(), '');
        check('color only keeps accessible label', node.getAttribute('aria-label'), 'Hidden label');
        check('color only fills the cell without fill setting', getComputedStyle(cell).backgroundColor, cssColor('orange'));
        check('color only has no readonly stripes', getComputedStyle(cell).backgroundImage, 'none');
        check('color only respects horizontal gutters', Math.abs(node.getBoundingClientRect().width - (cell.clientWidth - 8)) <= 1, true);
        data[0][1] = 'steelblue';
        render('Updated hidden label');
        check('color only updates dynamically', getComputedStyle(cell).backgroundColor, cssColor('steelblue'));
        data[0][1] = 'orange';
        display.colorOnly = false;
        display.fill = true;
        render('Visible label');
        cell.classList.add('exf-spreadsheet-invalid');
        check('validation background remains visible', getComputedStyle(cell).backgroundColor, cssColor('#ffebeb'));
        cell.classList.remove('exf-spreadsheet-invalid');
        display.fill = false;
        node = render('Unfilled');
        check('unfilled background reset', cell.classList.contains('exf-spreadsheet-color-filled'), false);
        check('unfilled text color', node.style.color, 'orange');
        check('unfilled readonly background retained', getComputedStyle(cell).backgroundImage.indexOf('repeating-linear-gradient') >= 0, true);
        Object.assign(display, {
            type: 'progress', color: '#FFEF9C', colorColumn: null, colorRange: true,
            colorScale: {'10': '~ERROR', '99': '~WARNING', '100': '~OK', '': 'lightgray'},
            min: 0, max: 100, text: null, textColumn: null, textScale: {}
        });
        [[0, 'orangered'], [10, 'orangered'], [50, 'yellow'], [99, 'yellow'], [100, 'lightgreen']].forEach(function(test) {
            node = render(test[0]);
            check('progress threshold ' + test[0], getComputedStyle(node.firstElementChild).backgroundColor, cssColor(test[1]));
            check('progress cell outer padding', getComputedStyle(cell).padding, '2px 4px');
            check('progress square border ' + test[0], getComputedStyle(node).borderTopLeftRadius, '0px');
            check('progress fill follows border ' + test[0], getComputedStyle(node.firstElementChild).borderTopLeftRadius, '0px');
            check('progress datagrid border ' + test[0], getComputedStyle(node).borderTop, '1px solid ' + cssColor('#ccc'));
            check('progress datagrid height ' + test[0], node.getBoundingClientRect().height, 20);
            check('progress no vertical padding ' + test[0], getComputedStyle(node).paddingTop, '0px');
            check('progress label padding ' + test[0], getComputedStyle(node.lastElementChild).padding, '0px 3px');
            check('progress clips overflow ' + test[0], getComputedStyle(node).overflow, 'hidden');
        });
        cell.parentElement.style.height = '25px';
        check('progress does not inflate minimum row height', cell.getBoundingClientRect().height, compactRowHeight);
        display.colorScale = {};
        ['#107e3e', 'yellow'].forEach(function(color) {
            display.color = color;
            [0, 35, 100].forEach(function(value) {
                node = render(value);
                var text = node.querySelector('.exf-spreadsheet-progress-text');
                var inside = cssColor(color === '#107e3e' ? 'white' : 'black');
                var expected = document.createElement('span');
                expected.style.backgroundImage = 'linear-gradient(to right, ' + inside + ' ' + value + '%, currentColor ' + value + '%)';
                check('two-region progress contrast ' + color + '/' + value, text.style.backgroundImage, expected.style.backgroundImage);
                check('progress gradient clips to text', getComputedStyle(text).backgroundClip, 'text');
                check('progress gradient reveals foreground', getComputedStyle(text).webkitTextFillColor, 'rgba(0, 0, 0, 0)');
                check('progress text aligns with bar width', Math.abs(text.getBoundingClientRect().width - node.clientWidth) <= 1, true);
                check('progress label remains plain scalar', text.textContent, String(value));
            });
        });
        render(null);
        check('empty progress is cleared', cell.children.length, 0);
        check('empty cell padding restored', getComputedStyle(cell).paddingTop, '4px');
        return checks + ' JEasyUI spreadsheet checks passed';
    } finally {
        host.remove();
    }
}





