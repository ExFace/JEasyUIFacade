const {test, expect} = require(process.env.PLAYWRIGHT_MODULE || '@playwright/test');

test.beforeEach(async ({page}) => {
    await page.goto('/bower-asset/jquery/dist/jquery.min.js');
    await page.setContent(`<!doctype html><html><head></head><body>
        <div class="exf-widget-group" style="position:absolute;width:240px;height:60px;overflow:hidden">
          <div class="panel" style="overflow:hidden">
           <div class="exf-widget-group panel-body" style="overflow-y:hidden">
            <div class="exf-display" style="width:240px;height:60px">
            <label>Call ID:</label>
            <span class="exf-labeled-item" style="white-space:nowrap">
                <a href="#">call_0x11f1a881eb7ec272a881c84bd653ed63</a>
            </span>
            </div>
           </div>
          </div>
        </div>
        <div style="position:absolute;left:248px;top:8px;width:240px;height:60px;background:white"></div>
    </body></html>`);
    await page.addStyleTag({url: '/exface/jeasyuifacade/Facades/js/themes/jeasyui.exface.css'});
    await page.mouse.move(1000, 800);
});

test('Display overflow is revealed beyond widget group wrappers on hover and keyboard focus', async ({page}) => {
    const display = page.locator('.exf-display');
    const read = () => display.evaluate(element => ({
        width: element.clientWidth,
        scrollWidth: element.scrollWidth,
        height: element.clientHeight,
        outerHeight: element.offsetHeight
    }));
    const tailIsVisible = () => display.locator('a').evaluate(element => {
        const range = document.createRange();
        range.setStart(element.firstChild, element.textContent.length - 4);
        range.setEnd(element.firstChild, element.textContent.length);
        const rect = range.getBoundingClientRect();
        return document.elementFromPoint(rect.x + rect.width / 2, rect.y + rect.height / 2)?.closest('a') === element;
    });
    await expect(display).toHaveCSS('overflow', 'hidden');
    const hidden = await read();
    expect(hidden.scrollWidth).toBeGreaterThan(hidden.width);
    expect(hidden.height).toBe(hidden.outerHeight);
    expect(await tailIsVisible()).toBe(false);
    await display.hover();
    await expect(display).toHaveCSS('overflow', 'visible');
    expect(await tailIsVisible()).toBe(true);
    const hovered = await read();
    expect(hovered.height).toBe(hidden.height);
    expect(hovered.outerHeight).toBe(hidden.outerHeight);
    await page.mouse.move(1000, 800);
    await expect(display).toHaveCSS('overflow', 'hidden');
    expect(await tailIsVisible()).toBe(false);
    await page.keyboard.press('Tab');
    await expect(display.locator('a')).toBeFocused();
    await expect(display).toHaveCSS('overflow', 'visible');
    expect(await tailIsVisible()).toBe(true);
});

test.describe('touch devices', () => {
    test.use({hasTouch: true, isMobile: true});

    test('Display scrolling does not depend on hover', async ({page}) => {
        expect(await page.evaluate(() => matchMedia('(hover: none)').matches)).toBe(true);
        await expect(page.locator('.exf-display')).toHaveCSS('overflow', 'auto');
    });
});
