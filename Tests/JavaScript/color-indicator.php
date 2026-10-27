<?php
require dirname(__DIR__, 4) . '/autoload.php';

use exface\Core\Interfaces\Facades\FacadeInterface;
use exface\Core\Interfaces\WidgetInterface;
use exface\JEasyUIFacade\Facades\Elements\EuiColorIndicator;

$indicator = new class extends EuiColorIndicator {
    public $colorOnly = false;
    public $filled = true;

    public function __construct(?WidgetInterface $widget = null, ?FacadeInterface $facade = null) {}
    public function getId() { return 'color-indicator-regression'; }
    public function getWidget()
    {
        return new class($this->colorOnly, $this->filled) {
            private $colorOnly;
            private $filled;
            public function __construct(bool $colorOnly, bool $filled) { $this->colorOnly = $colorOnly; $this->filled = $filled; }
            public function getWidth() { return new class { public function isUndefined() { return true; } }; }
            public function getHeight() { return $this->getWidth(); }
            public function getFill() { return $this->filled; }
            public function getColorOnly() { return $this->colorOnly; }
            public function getValueDataType() { return null; }
            public function hasColorScale() { return false; }
            public function getDataColumnName() { return 'label'; }
            public function getColorBinding() {
                return new class {
                    public function isBoundToDataColumn() { return true; }
                    public function getDataColumnName() { return 'color'; }
                    public function hasValue() { return false; }
                };
            }
        };
    }
    public function getFacade()
    {
        return new class {
            public function getDataTypeFormatter($type) { return $this; }
            public function getJsEmptyText($fallback) { return 'null'; }
        };
    }
    protected function buildJsValueFormatter($value) { return $value; }

    public function buildTestHtml() : string
    {
        return $this->buildHtmlIndicator(null, 'exfph-text', null, '#a85a00');
    }
};
$labelHtml = $indicator->buildTestHtml();
$labelDecorator = $indicator->buildJsValueDecorator('value', 'row');
$indicator->colorOnly = true;
$swatchHtml = $indicator->buildTestHtml();
$swatchDecorator = $indicator->buildJsValueDecorator('value', 'row');
$indicator->colorOnly = false;
$indicator->filled = false;
$textDecorator = $indicator->buildJsValueDecorator('value', 'row');
$easyui = is_dir(dirname(__DIR__, 4) . '/bower-asset/jeasyui') ? 'bower-asset/jeasyui' : 'salt/jeasyui';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Color indicator layout regression</title>
    <link rel="stylesheet" href="../../../../<?= $easyui ?>/themes/default/easyui.css">
    <link rel="stylesheet" href="../../Facades/js/themes/jeasyui.exface.css">
    <script src="../../../../bower-asset/jquery/dist/jquery.min.js"></script>
    <script src="../../../../<?= $easyui ?>/jquery.easyui.min.js"></script>
    <script src="../../../core/Facades/AbstractAjaxFacade/js/exfColorTools.js"></script>
</head>
<body>
    <pre id="results">Running...</pre>
    <script>
        var colorLabelHtml = <?= json_encode($labelHtml) ?>;
        var colorSwatchHtml = <?= json_encode($swatchHtml) ?>;
        function colorLabelFormatter(value, row) {
            return <?= $labelDecorator ?>;
        }
        function colorSwatchFormatter(value, row) {
            return <?= $swatchDecorator ?>;
        }
        function colorTextFormatter(value, row) {
            return <?= $textDecorator ?>;
        }
    </script>
    <script src="color-indicator.js"></script>
</body>
</html>
