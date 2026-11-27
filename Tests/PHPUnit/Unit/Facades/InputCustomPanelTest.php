<?php

namespace exface\JEasyUIFacade\Tests\PHPUnit\Unit\Facades;

use DOMDocument;
use DOMXPath;
use exface\Core\CommonLogic\ContextManager;
use exface\Core\CommonLogic\Model\MetaObject;
use exface\Core\CommonLogic\Model\Model;
use exface\Core\CommonLogic\Selectors\FacadeSelector;
use exface\Core\CommonLogic\UxonObject;
use exface\Core\CommonLogic\Workbench;
use exface\Core\Factories\UiPageFactory;
use exface\Core\Widgets\InputCustom;
use exface\JEasyUIFacade\Facades\Elements\EuiInputCustom;
use exface\JEasyUIFacade\Facades\JEasyUIFacade;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class InputCustomPanelTest extends TestCase
{
    public function testOnlyToolbarPanelOverridesOuterInputPadding() : void
    {
        $wrapperStyle = 'string(//div[contains(concat(" ", normalize-space(@class), " "), " exf-inputcustom ")]/@style)';
        self::assertStringContainsString('padding:0;', $this->renderInput([
            'buttons' => [['caption' => 'Example action']]
        ])->evaluate($wrapperStyle));
        self::assertStringNotContainsString('padding:', $this->renderInput([])->evaluate($wrapperStyle));
    }

    public function testToolbarAndContentShareANativePanel() : void
    {
        $xpath = $this->renderInput(['buttons' => [['caption' => 'Example action']]]);

        $panel = '//div[contains(concat(" ", normalize-space(@class), " "), " easyui-panel ")]';
        $toolbar = $panel . '/div[contains(concat(" ", normalize-space(@class), " "), " datagrid-toolbar ")]';
        self::assertSame('Caption & title', html_entity_decode($xpath->evaluate("string({$panel}/@title)")));
        self::assertSame('Example action', $xpath->evaluate("normalize-space({$toolbar}//a)"));
        self::assertSame('Custom content', $xpath->evaluate("normalize-space({$toolbar}/following-sibling::div)"));
        self::assertSame(0.0, $xpath->evaluate('count(//label)'));
        self::assertSame('fit:false', $xpath->evaluate("string({$panel}/@data-options)"));
    }

    public function testInputWithoutToolbarKeepsItsFormLabel() : void
    {
        $xpath = $this->renderInput([]);

        self::assertSame('Caption & title', $xpath->evaluate('normalize-space(//label)'));
        self::assertSame(0.0, $xpath->evaluate('count(//div[contains(@class, "easyui-panel")])'));
    }

    private function renderInput(array $uxon) : DOMXPath
    {
        $workbench = new Workbench([
            'SERVER.INSTALLATION_NAME' => 'input-toolbar-test',
            'WIDGET.BUTTON.HIDDEN_IF_ACCESS_DENIED' => true
        ]);
        (new ReflectionProperty(Workbench::class, 'mm'))->setValue($workbench, new Model($workbench));
        (new ReflectionProperty(Workbench::class, 'context'))->setValue($workbench, new ContextManager($workbench));
        $widget = new InputCustom(UiPageFactory::createEmpty($workbench));
        $widget->setMetaObject(new MetaObject($workbench->model()));
        $widget->importUxonObject(new UxonObject($uxon + [
            'caption' => 'Caption & title',
            'html' => '<div>Custom content</div>'
        ]));

        $facade = new JEasyUIFacade(new FacadeSelector($workbench, JEasyUIFacade::class));
        $element = new EuiInputCustom($widget, $facade);
        $document = new DOMDocument();
        $document->loadHTML($element->buildHtml(), LIBXML_NOERROR | LIBXML_NOWARNING);
        return new DOMXPath($document);
    }
}
