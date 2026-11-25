const {test, expect} = require(process.env.PLAYWRIGHT_MODULE || '@playwright/test');
const {execFileSync} = require('node:child_process');
const {resolve} = require('node:path');

function generateGroupClasses(height = '') {
    return execFileSync('php', ['-r', `
require $argv[1];
$workbench = new exface\\Core\\CommonLogic\\Workbench(['SERVER.INSTALLATION_NAME' => 'widget-group-height-browser-test']);
$workbench->getConfig()->loadConfigFile($argv[2]);
$page = exface\\Core\\Factories\\UiPageFactory::createBlank($workbench, '');
$group = new exface\\Core\\Widgets\\WidgetGroup($page);
$group->setWidth(2);
if ($argv[3] !== '') {
    $group->setHeight($argv[3]);
}
$input = new exface\\Core\\Widgets\\InputText($page, $group);
$input->setWidth('100%');
$group->addWidget($input);
$facade = new exface\\JEasyUIFacade\\Facades\\JEasyUIFacade(
    new exface\\Core\\CommonLogic\\Selectors\\FacadeSelector($workbench, exface\\JEasyUIFacade\\Facades\\JEasyUIFacade::class)
);
$element = new exface\\JEasyUIFacade\\Facades\\Elements\\EuiWidgetGroup($group, $facade);
echo $element->buildCssElementClass();
`, resolve(__dirname, '../../../../autoload.php'), resolve(__dirname, '../../../core/Config/exface.Core.config.json'),
    height], {encoding: 'utf8'});
}

test.beforeEach(async ({page}) => {
    await page.goto('/bower-asset/jquery/dist/jquery.min.js');
    await page.setContent('<!doctype html><html><head></head><body></body></html>');
    await page.addScriptTag({url: '/bower-asset/jquery/dist/jquery.min.js'});
    await page.addScriptTag({url: '/salt/jeasyui/jquery.easyui.min.js'});
    await page.addStyleTag({url: '/exface/jeasyuifacade/Facades/js/themes/material/easyui.css'});
    await page.addStyleTag({url: '/exface/jeasyuifacade/Facades/js/themes/jeasyui.exface.css'});
});

async function mountGroup(page, height = '') {
    const classes = generateGroupClasses(height);
    await page.evaluate(({classes, height}) => {
        document.body.innerHTML = `<div id="group-wrapper" class="exf-grid-item ${classes}" style="width:700px;height:${height ? '300px' : 'auto'};padding:4px;box-sizing:border-box">
            <div id="group" class="easyui-panel ${classes}" title="Tool Call" data-options="fit:true,border:false">
                <textarea style="width:100%;height:64px;box-sizing:border-box;vertical-align:middle">Tool call</textarea>
            </div>
        </div>`;
        $.parser.parse(document.body);
    }, {classes, height});
}

test('auto-height group spanning two columns does not grow on repeated layout', async ({page}) => {
    await mountGroup(page);
    const heights = await page.evaluate(() => {
        const values = [];
        for (let i = 0; i < 30; i++) {
            $('#group').panel('resize');
            values.push(document.querySelector('#group-wrapper').getBoundingClientRect().height);
        }
        return values;
    });
    expect(heights[0]).toBeGreaterThan(64);
    expect(Math.max(...heights) - Math.min(...heights)).toBeLessThanOrEqual(1);
});

test('explicit max-height group continues to fill the height assigned by its container', async ({page}) => {
    await mountGroup(page, 'max');
    const sizes = await page.evaluate(() => {
        const values = [];
        for (const height of [300, 450, 250]) {
            $('#group-wrapper').height(height);
            $('#group').panel('resize');
            const wrapper = document.querySelector('#group-wrapper');
            const panel = document.querySelector('#group').parentElement;
            values.push({available: wrapper.clientHeight, actual: panel.getBoundingClientRect().height});
        }
        return values;
    });
    for (const size of sizes) {
        expect(size.actual).toBeGreaterThanOrEqual(size.available - 8);
        expect(size.actual).toBeLessThanOrEqual(size.available);
    }
    expect(sizes[1].actual - sizes[0].actual).toBe(150);
    expect(sizes[2].actual - sizes[1].actual).toBe(-200);
});
