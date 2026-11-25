const {test, expect} = require(process.env.PLAYWRIGHT_MODULE || '@playwright/test');
const {execFileSync} = require('node:child_process');
const {resolve} = require('node:path');

function generateTabs() {
    return JSON.parse(execFileSync('php', ['-r', `
require $argv[1];
$workbench = new exface\\Core\\CommonLogic\\Workbench(['SERVER.INSTALLATION_NAME' => 'tab-visibility-browser-test']);
$workbench->getConfig()->loadConfigFile($argv[2]);
$workbench->start();
$page = exface\\Core\\Factories\\UiPageFactory::createBlank($workbench, '');
$tabs = new exface\\Core\\Widgets\\Tabs($page);
$tabs->setId('tabs');
$facade = new exface\\JEasyUIFacade\\Facades\\JEasyUIFacade(
    new exface\\Core\\CommonLogic\\Selectors\\FacadeSelector($workbench, exface\\JEasyUIFacade\\Facades\\JEasyUIFacade::class)
);
$scripts = [];
foreach (['Message', 'Individual tool calls', 'Disabled', 'Data'] as $index => $caption) {
    $tab = new exface\\Core\\Widgets\\Tab($page, $tabs);
    $tab->setId('tab' . $index);
    $tab->setCaption($caption);
    $tabs->addWidget($tab);
    $element = $facade->getElement($tab);
    $scripts[] = [
        'id' => $element->getId(),
        'caption' => $caption,
        'hide' => $element->buildJsCallFunction('hide'),
        'show' => $element->buildJsCallFunction('show'),
        'activate' => $element->buildJsSetActive(true)
    ];
}
echo json_encode(['id' => $facade->getElement($tabs)->getId(), 'tabs' => $scripts], JSON_THROW_ON_ERROR);
`, resolve(__dirname, '../../../../autoload.php'), resolve(__dirname, '../../../core/Config/exface.Core.config.json')],
    {encoding: 'utf8'}));
}

let fixture;

test.beforeEach(async ({page}) => {
    fixture = generateTabs();
    await page.goto('/bower-asset/jquery/dist/jquery.min.js');
    await page.setContent('<!doctype html><html><head></head><body></body></html>');
    await page.addScriptTag({url: '/bower-asset/jquery/dist/jquery.min.js'});
    await page.addScriptTag({url: '/salt/jeasyui/jquery.easyui.min.js'});
    await page.addStyleTag({url: '/exface/jeasyuifacade/Facades/js/themes/material/easyui.css'});
    await page.addStyleTag({url: '/exface/jeasyuifacade/Facades/js/themes/jeasyui.exface.css'});
    await page.evaluate(fixture => {
        document.body.innerHTML = `<div id="${fixture.id}">${fixture.tabs.map((tab, index) =>
            `<div id="${tab.id}" title="${tab.caption}" ${index === 2 ? 'data-options="disabled:true"' : ''}>${tab.caption} content</div>`
        ).join('')}</div>`;
        $(`#${fixture.id}`).tabs({width: 650, height: 300});
    }, fixture);
});

async function run(page, index, action) {
    await page.evaluate(script => { new Function(script)(); }, fixture.tabs[index][action]);
}

const header = (page, index) => page.locator('.tabs-header li').nth(index);
const panel = (page, index) => page.locator(`[id="${fixture.tabs[index].id}"]`);

test('hiding an inactive tab hides its header and showing it preserves selection and indexes', async ({page}) => {
    await run(page, 1, 'hide');
    await expect(header(page, 1)).toBeHidden();
    await expect(panel(page, 1)).toBeHidden();
    await expect(panel(page, 0)).toBeVisible();
    await expect(page.locator('.tabs-header li')).toHaveCount(4);
    await run(page, 1, 'activate');
    await expect(panel(page, 0)).toBeVisible();
    await expect(panel(page, 1)).toBeHidden();
    await run(page, 1, 'show');
    await expect(header(page, 1)).toBeVisible();
    await expect(panel(page, 1)).toBeHidden();
    await run(page, 1, 'activate');
    await expect(panel(page, 1)).toBeVisible();
});

test('hiding the selected tab skips hidden and disabled tabs and recovers when a tab is shown', async ({page}) => {
    await run(page, 1, 'activate');
    await run(page, 0, 'hide');
    await run(page, 1, 'hide');
    await expect(panel(page, 3)).toBeVisible();
    await expect(header(page, 1)).toBeHidden();
    await run(page, 3, 'hide');
    await expect(page.locator('.tabs-header .tabs-selected')).toHaveCount(0);
    await run(page, 1, 'show');
    await expect(panel(page, 1)).toBeVisible();
    await expect(header(page, 1)).toHaveClass(/tabs-selected/);
});

test('hide and show do not enable a disabled tab', async ({page}) => {
    await run(page, 2, 'hide');
    await expect(header(page, 2)).toBeHidden();
    await run(page, 2, 'show');
    await expect(header(page, 2)).toBeVisible();
    await expect(header(page, 2)).toHaveClass(/tabs-disabled/);
    await run(page, 2, 'activate');
    await expect(panel(page, 2)).toBeHidden();
    await expect(panel(page, 0)).toBeVisible();
});
