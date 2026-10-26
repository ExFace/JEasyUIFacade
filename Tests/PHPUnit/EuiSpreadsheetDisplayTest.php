<?php
namespace exface\JEasyUIFacade\Tests\PHPUnit;

use exface\Core\Widgets\DataSpreadSheet;
use exface\JEasyUIFacade\Facades\Elements\EuiDataSpreadSheet;
use PHPUnit\Framework\TestCase;

class EuiSpreadsheetDisplayTest extends TestCase
{
    public function testGeneratedGridRootIncludesDisplayStyleScope() : void
    {
        $widget = $this->createMock(DataSpreadSheet::class);
        $widget->method('getWidgetType')->willReturn('DataSpreadSheet');
        $element = $this->getMockBuilder(EuiDataSpreadSheet::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getWidget', 'getId'])
            ->getMock();
        $element->method('getWidget')->willReturn($widget);
        $element->method('getId')->willReturn('spreadsheet');

        // Exercise the real HTML generator, not a hand-authored browser fixture.
        $method = new \ReflectionMethod(EuiDataSpreadSheet::class, 'buildHtmlJExcel');
        $method->setAccessible(true);
        $html = $method->invoke($element);
        $document = new \DOMDocument();
        $document->loadHTML($html);
        $root = $document->getElementById('spreadsheet');
        $this->assertNotNull($root);
        $this->assertContains('exf-spreadsheet', explode(' ', $root->getAttribute('class')));
        $this->assertContains('exfw-DataSpreadSheet', explode(' ', $root->getAttribute('class')));
    }
}