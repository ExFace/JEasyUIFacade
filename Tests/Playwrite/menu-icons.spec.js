const {test, expect} = require(process.env.PLAYWRIGHT_MODULE || '@playwright/test');
const {execFileSync} = require('node:child_process');
const {resolve} = require('node:path');

test.setTimeout(90_000);

const svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><title>chart-gantt</title><path d="M2,5H10V2H12V22H10V18H6V15H10V13H4V10H10V8H2V5M14,5H17V8H14V5M14,10H19V13H14V10M14,15H22V18H14V15Z" /></svg>';

/** Render actual menu items using the local installation's Workbench and a blank form. */
function generateMenu() {
    const buttons = [
        {id: 'mono', caption: 'Schedule', icon: svg},
        {id: 'explicit', caption: 'Explicit SVG', icon: svg, icon_set: 'svg'},
        {id: 'colored', caption: 'Colored', icon: svg.replace('<svg ', '<svg fill="#ff0000" '), icon_set: 'svg_colored'},
        {id: 'hidden', caption: 'Hidden icon', icon: svg, show_icon: false},
        {id: 'font', caption: 'Calendar', icon: 'calendar'},
        {id: 'custom', caption: 'Custom font', icon: 'example', icon_set: 'custom'},
        {id: 'empty', caption: 'No icon'},
        {id: 'disabled', caption: 'Disabled', icon: svg, disabled: true}
    ];
    return JSON.parse(execFileSync('php', ['-r', `
require $argv[1];
$workbench = new exface\\Core\\CommonLogic\\Workbench(['SERVER.INSTALLATION_NAME' => 'menu-icons-browser-test']);
$workbench->getConfig()->loadConfigFile($argv[2]);
$workbench->start();
$page = exface\\Core\\Factories\\UiPageFactory::createBlank($workbench, '');
$form = new exface\\Core\\Widgets\\Form($page);
$form->setId('form');
$menu = new exface\\Core\\Widgets\\Menu($page, $form);
$menu->setId('menu');
$menu->setCaption('Actions');
$menu->importUxonObject(new exface\\Core\\CommonLogic\\UxonObject(json_decode($argv[3], true, 512, JSON_THROW_ON_ERROR)));
$facade = new exface\\JEasyUIFacade\\Facades\\JEasyUIFacade(
    new exface\\Core\\CommonLogic\\Selectors\\FacadeSelector($workbench, exface\\JEasyUIFacade\\Facades\\JEasyUIFacade::class)
);
$element = new exface\\JEasyUIFacade\\Facades\\Elements\\EuiMenu($menu, $facade);
$ids = [];
foreach ($menu->getButtons() as $button) {
    $ids[$button->getId()] = $facade->getElement($button)->getId();
}
echo json_encode(['html' => $element->buildHtmlButtons(), 'ids' => $ids], JSON_THROW_ON_ERROR);
`, resolve(__dirname, '../../../../autoload.php'), resolve(__dirname, '../../../core/Config/exface.Core.config.json'),
    JSON.stringify({button_groups: [{caption: 'Charts', buttons: buttons.slice(0, 4)}, {caption: 'Other', buttons: buttons.slice(4)}]})],
    {encoding: 'utf8'}));
}

test.beforeEach(async ({page}) => {
    await page.goto('/bower-asset/jquery/dist/jquery.min.js');
    await page.setContent('<!doctype html><html><head></head><body></body></html>');
    await page.addScriptTag({url: '/bower-asset/jquery/dist/jquery.min.js'});
    await page.addScriptTag({url: '/bower-asset/jeasyui/jquery.easyui.min.js'});
    await page.addStyleTag({url: '/exface/jeasyuifacade/Facades/js/themes/material/easyui.css'});
    await page.addStyleTag({url: '/exface/jeasyuifacade/Facades/js/themes/jeasyui.exface.css'});
});

/** Parse the shared item markup as either an inline menu or a MenuButton dropdown. */
async function mountMenu(page, dropdown = false) {
    const {html, ids} = generateMenu();
    await page.evaluate(({html, dropdown}) => {
        document.body.innerHTML = dropdown
            ? `<a id="trigger" class="easyui-menubutton" data-options="menu:'#items'">Actions</a><div id="items">${html}</div>`
            : `<div id="items" class="easyui-menu" data-options="inline:true">${html}</div>`;
        $.parser.parse(document.body);
        if (dropdown) {
            $('#trigger').trigger('click');
        }
    }, {html, dropdown});
    return Object.fromEntries(Object.entries(ids).map(([name, id]) => [name, page.locator(`[id="${id}"]`)]));
}

for (const dropdown of [false, true]) {
    test(`${dropdown ? 'dropdown' : 'inline'} menu renders mixed SVG and font icons without changing item behavior`, async ({page}) => {
        const items = await mountMenu(page, dropdown);
        const icon = items.mono.locator('.menu-icon.exf-svg-icon > svg');
        await expect(icon).toBeVisible();
        await expect(icon.locator('title')).toHaveText('chart-gantt');
        await expect(icon.locator('path')).toHaveAttribute('d', 'M2,5H10V2H12V22H10V18H6V15H10V13H4V10H10V8H2V5M14,5H17V8H14V5M14,10H19V13H14V10M14,15H22V18H14V15Z');
        expect(await icon.evaluate(element => {
            const rect = element.getBoundingClientRect();
            return {width: rect.width, height: rect.height};
        })).toEqual({width: 16, height: 16});
        await expect(items.mono.locator('.menu-icon')).toHaveCount(1);
        await expect(items.explicit.locator('.menu-icon.exf-svg-icon > svg')).toBeVisible();
        await expect(items.colored.locator('.menu-icon.exf-svg-colored > svg')).toHaveCSS('fill', 'rgb(255, 0, 0)');
        await items.mono.hover();
        expect(await icon.evaluate(element => getComputedStyle(element).fill))
            .toBe(await items.mono.locator('.menu-text').evaluate(element => getComputedStyle(element).color));
        await expect(items.font.locator('.menu-icon.fa.fa-calendar')).toHaveCount(1);
        await expect(items.custom.locator('.menu-icon.custom.custom-example')).toHaveCount(1);
        await expect(items.hidden.locator('.menu-icon')).toHaveCount(0);
        await expect(items.hidden.locator('.menu-text')).toHaveText('Hidden icon');
        await expect(items.empty.locator('.menu-icon')).toHaveCount(0);
        await expect(items.disabled).toHaveClass(/menu-item-disabled/);
        await expect(items.disabled.locator('.menu-icon > svg')).toBeVisible();
        await expect(page.locator('.exf-menu-group-title')).toHaveCount(2);
        await expect(page.locator('.menu-sep')).toHaveCount(2);
        await expect(items.mono).toHaveAttribute('onclick', /click\(\)/);
        if (dropdown) {
            await page.evaluate(() => {
                $('#items').menu('hide');
                $('#trigger').trigger('click');
            });
            await expect(icon).toBeVisible();
            await expect(items.mono.locator('.menu-icon')).toHaveCount(1);
        }
    });
}
