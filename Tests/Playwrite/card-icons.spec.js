const {test, expect} = require(process.env.PLAYWRIGHT_MODULE || '@playwright/test');
const {execFileSync} = require('node:child_process');
const {resolve} = require('node:path');

test.setTimeout(90_000);

const svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><title>chart-gantt</title><path d="M2,5H10V2H12V22H10V18H6V15H10V13H4V10H10V8H2V5M14,5H17V8H14V5M14,10H19V13H14V10M14,15H22V18H14V15Z" /></svg>';

/** Generate real card options without starting Workbench or loading a persisted metamodel. */
function generateCardOptions(icon, iconSet, hideCaption = false) {
    return execFileSync('php', ['-r', `
require $argv[1];
$workbench = new exface\\Core\\CommonLogic\\Workbench(['SERVER.INSTALLATION_NAME' => 'card-icons-browser-test']);
$workbench->getConfig()->loadConfigFile($argv[2]);
$card = new exface\\Core\\Widgets\\Card(exface\\Core\\Factories\\UiPageFactory::createBlank($workbench, ''));
$card->setId('card');
$card->setCaption('Schedule');
$card->setHideCaption($argv[5] === 'true');
$card->setColumnsInGrid(1);
if ($argv[3] !== '') {
    $card->setIcon($argv[3]);
}
if ($argv[4] !== '') {
    $card->setIconSet($argv[4]);
}
$facade = new exface\\JEasyUIFacade\\Facades\\JEasyUIFacade(
    new exface\\Core\\CommonLogic\\Selectors\\FacadeSelector($workbench, exface\\JEasyUIFacade\\Facades\\JEasyUIFacade::class)
);
$element = new exface\\JEasyUIFacade\\Facades\\Elements\\EuiCard($card, $facade);
echo $element->buildJsDataOptions();
`, resolve(__dirname, '../../../../autoload.php'), resolve(__dirname, '../../../core/Config/exface.Core.config.json'),
    icon, iconSet, String(hideCaption)], {encoding: 'utf8'});
}

test.beforeEach(async ({page}) => {
    await page.goto('/bower-asset/jquery/dist/jquery.min.js');
    await page.setContent('<!doctype html><html><head></head><body></body></html>');
    await page.addScriptTag({url: '/bower-asset/jquery/dist/jquery.min.js'});
    await page.addScriptTag({url: '/bower-asset/jeasyui/jquery.easyui.min.js'});
    await page.addStyleTag({url: '/exface/jeasyuifacade/Facades/js/themes/material/easyui.css'});
    await page.addStyleTag({url: '/exface/jeasyuifacade/Facades/js/themes/jeasyui.exface.css'});
});

/** Initialize through the HTML data-options attribute, including its escaping round trip. */
async function mountCard(page, options, hideCaption = false) {
    await page.evaluate(({options, hideCaption}) => {
        document.body.innerHTML = `<div id="card" class="easyui-panel exf-card" ${hideCaption ? '' : 'title="Schedule"'} data-options="${options}" style="color: rgb(12, 34, 56)"></div>`;
        $.parser.parse(document.body);
        $('#card').panel('resize', {width: 320, height: 180});
    }, {options, hideCaption});
}

test('card renders the inline SVG at header icon size and survives reopening', async ({page}) => {
    await mountCard(page, generateCardOptions(svg, ''));
    const icon = page.locator('.panel-header .panel-icon.exf-svg-icon > svg');
    await expect(icon).toHaveCount(1);
    await expect(icon.locator('title')).toHaveText('chart-gantt');
    await expect(icon.locator('path')).toHaveAttribute('d', 'M2,5H10V2H12V22H10V18H6V15H10V13H4V10H10V8H2V5M14,5H17V8H14V5M14,10H19V13H14V10M14,15H22V18H14V15Z');
    expect(await icon.evaluate(element => {
        const svgRect = element.getBoundingClientRect();
        const iconRect = element.parentElement.getBoundingClientRect();
        return {width: svgRect.width, height: svgRect.height, iconWidth: iconRect.width, iconHeight: iconRect.height};
    })).toEqual({width: 16, height: 16, iconWidth: 16, iconHeight: 16});
    expect(await icon.evaluate(element => getComputedStyle(element).fill))
        .toBe(await icon.evaluate(element => getComputedStyle(element).color));
    await page.evaluate(() => $('#card').panel('close').panel('open').panel('collapse').panel('expand'));
    await expect(icon).toHaveCount(1);
    await expect(icon).toBeVisible();
});

test('colored SVG retains its fill and quoted SVG attributes survive data-options', async ({page}) => {
    const colored = svg.replace('<svg ', '<svg fill="#ff0000" ').replace('chart-gantt', "Gantt's &quot;schedule&quot;");
    await mountCard(page, generateCardOptions(colored, 'svg_colored'));
    const icon = page.locator('.panel-icon.exf-svg-colored > svg');
    await expect(icon).toHaveCSS('fill', 'rgb(255, 0, 0)');
    await expect(icon.locator('title')).toHaveText('Gantt\'s "schedule"');
});

test('font icons, absent icons and hidden captions keep their existing behavior', async ({page}) => {
    await mountCard(page, generateCardOptions('calendar', ''));
    await expect(page.locator('.panel-icon.fa.fa-calendar')).toHaveCount(1);
    await expect(page.locator('.panel-icon svg')).toHaveCount(0);
    await mountCard(page, generateCardOptions('', ''));
    await expect(page.locator('.panel-icon')).toHaveCount(0);
    await mountCard(page, generateCardOptions(svg, '', true), true);
    await expect(page.locator('.panel-header')).toHaveCount(0);
});
