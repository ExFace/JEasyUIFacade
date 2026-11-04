const {test, expect} = require(process.env.PLAYWRIGHT_MODULE || '@playwright/test');
const {execFileSync} = require('node:child_process');
const {resolve} = require('node:path');

// Exercise the real UXON setting and generated formatter without a persisted metamodel.
function generateMarkdownIntegration(enabled) {
    return JSON.parse(execFileSync('php', ['-r', `
require $argv[1];
$workbench = new exface\\Core\\CommonLogic\\Workbench(['SERVER.INSTALLATION_NAME' => 'comments-markdown-browser-test']);
$workbench->getConfig()->loadConfigFile($argv[2]);
$feed = new exface\\Core\\Widgets\\CommentsFeed(exface\\Core\\Factories\\UiPageFactory::createBlank($workbench, ''));
if ($argv[3] === 'true') {
    $feed->importUxonObject(new exface\\Core\\CommonLogic\\UxonObject(['enable_markdown' => true]));
}
$class = new ReflectionClass(exface\\JEasyUIFacade\\Facades\\Elements\\EuiCommentsFeed::class);
$element = $class->newInstanceWithoutConstructor();
$setWidget = $class->getMethod('setWidget');
$setWidget->setAccessible(true);
$setWidget->invoke($element, $feed);
$id = new ReflectionProperty(exface\\Core\\Facades\\AbstractAjaxFacade\\Elements\\AbstractJqueryElement::class, 'id');
$id->setAccessible(true);
$id->setValue($element, 'comments');
$method = $class->getMethod('buildJsCommentsMarkdownInit');
$method->setAccessible(true);
 $result = ['formatter' => $method->invoke($element)];
if (!$feed->getEnableMarkdown()) {
    $headers = $class->getMethod('buildHtmlHeadTagsForComments');
    $headers->setAccessible(true);
    $result['headers'] = $headers->invoke($element);
}
echo json_encode($result, JSON_THROW_ON_ERROR);
`, resolve(__dirname, '../../../../autoload.php'), resolve(__dirname, '../../../core/Config/exface.Core.config.json'), String(enabled)], {encoding: 'utf8'}));
}

const {formatter} = generateMarkdownIntegration(true);
const plainTextIntegration = generateMarkdownIntegration(false);

test.beforeEach(async ({page}) => {
    await page.goto('/exface/jeasyuifacade/Tests/Playwrite/markian.html');
    await page.addScriptTag({url: '/bower-asset/jquery/dist/jquery.min.js'});
    await page.addScriptTag({url: '/bower-asset/jquery-comments/js/jquery-comments.js'});
    await page.addStyleTag({url: '/bower-asset/jquery-comments/css/jquery-comments.css'});
    await page.evaluate(({formatter}) => {
        const host = document.createElement('div');
        host.id = 'comments';
        document.body.appendChild(host);
        window.commentSource = '**Bold** and *italic*\nsecond line\n\n- first\n- second\n\n```js\nconst value = 1;\n```\n\n[Guide](https://example.com)\n\n<script>alert(1)</script>\n\n[bad](javascript:alert(1))';
        const install = new Function('oMarkdownOptions', formatter);
        window.installCommentsMarkdown = () => install({softbreaks: true});
        $(host).comments({
            enableAttachments: false,
            enablePinging: false,
            enableHashtags: false,
            fieldMappings: {id: 'UID', content: 'BODY', created: 'CREATED', modified: 'MODIFIED', fullname: 'AUTHOR', createdByCurrentUser: 'MINE'},
            getComments(success) {
                installCommentsMarkdown();
                success([
                    {UID: '1', BODY: commentSource, CREATED: '2026-11-01', MODIFIED: '2026-11-02', AUTHOR: 'Test', MINE: true},
                    {UID: '2', BODY: '', CREATED: '2026-11-01', AUTHOR: 'Test'}
                ]);
            }
        });
        window.comments = $(host).data('comments');
    }, {formatter});
});

test('comments render safe Markdown while editing and saving retain source', async ({page}) => {
    const comment = page.locator('#comments li.comment[data-id="1"]');
    await expect(comment.locator('strong')).toHaveText('Bold');
    await expect(comment.locator('em')).toHaveText('italic');
    await expect(comment.locator('.markian-viewer ul li')).toHaveText(['first', 'second']);
    await expect(comment.locator('pre code')).toContainText('const value = 1;');
    await expect(comment.locator('.markian-viewer br')).toHaveCount(1);
    await expect(comment.locator('a[href="https://example.com/"]')).toHaveCount(1);
    await expect(comment.locator('script, a[href^="javascript:"]')).toHaveCount(0);
    await expect(comment.locator('.content > time.edited')).toHaveCount(1);
    await expect(page.locator('#comments li.comment[data-id="2"] .markian-viewer')).toHaveCount(1);
    expect(await page.evaluate(() => comments.commentsById['1'].content)).toBe(await page.evaluate(() => commentSource));
    await comment.locator('button.edit').click();
    expect(await page.evaluate(() => comments.getTextareaContent($('#comments li.comment[data-id="1"] .textarea'))))
        .toBe(await page.evaluate(() => commentSource));
    await expect(comment.locator('.textarea .markian-viewer')).toHaveCount(0);
    await page.evaluate(() => {
        comments.options.putComment = (row, success) => {
            window.savedComment = row;
            success(row);
        };
        $('#comments li.comment[data-id="1"] .textarea').append('<br>').append(document.createTextNode('**Saved**')).trigger('input');
    });
    await comment.locator('.update').click();
    expect(await page.evaluate(() => savedComment.BODY)).toBe(await page.evaluate(() => commentSource + '\n**Saved**'));
    await expect(comment.locator('.markian-viewer strong')).toHaveText(['Bold', 'Saved']);
});

test('comment viewers survive rerendering and release detached mounts', async ({page}) => {
    await page.evaluate(async () => {
        const mount = document.querySelector('#comments [data-id="1"] .content > div');
        window.oldCommentMount = mount;
        await mount._exfMarkian.ready;
        comments.render();
    });
    await expect.poll(() => page.evaluate(() => oldCommentMount._exfMarkian === undefined)).toBe(true);
    await expect(page.locator('#comments [data-id="1"] .markian-viewer')).toHaveCount(1);
    await page.evaluate(() => {
        comments.commentsById['1'].content = '**Updated**';
        comments.reRenderComment('1');
        comments.createComment({UID: '3', BODY: '**Created**', CREATED: '2026-11-03', AUTHOR: 'Test'});
    });
    await expect(page.locator('#comments [data-id="1"] strong')).toHaveText('Updated');
    await expect(page.locator('#comments [data-id="3"] strong')).toHaveText('Created');
    expect(await page.evaluate(() => {
        const formatter = comments.getFormattedCommentContent;
        installCommentsMarkdown();
        return formatter === comments.getFormattedCommentContent;
    })).toBe(true);
    await page.evaluate(() => comments.fetchDataAndRender());
    await expect(page.locator('#comments [data-id="1"] strong')).toHaveText('Bold');
    await page.evaluate(() => {
        window.removedCommentMount = document.querySelector('#comments [data-id="1"] .content > div');
        document.getElementById('comments').remove();
    });
    await expect.poll(() => page.evaluate(() => removedCommentMount._exfMarkian === undefined)).toBe(true);
});

test('default feeds keep plain text and do not request Markian assets', async ({page}) => {
    expect(plainTextIntegration.formatter).toBe('');
    expect(plainTextIntegration.headers).toHaveLength(2);
    expect(plainTextIntegration.headers.join('')).not.toContain('markian');
    await page.evaluate(() => {
        const host = $('<div id="plain-comments">').appendTo(document.body);
        host.comments({getComments(success) {
            success([{id: 'plain', content: '**Not bold**\nsecond line', created: '2026-11-01', fullname: 'Test'}]);
        }});
    });
    await expect(page.locator('#plain-comments .content')).toHaveText('**Not bold**\nsecond line');
    await expect(page.locator('#plain-comments strong, #plain-comments .markian-viewer')).toHaveCount(0);
});
