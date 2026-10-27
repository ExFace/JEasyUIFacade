<?php
require dirname(__DIR__, 4) . '/autoload.php';

use exface\Core\DataTypes\NumberEnumDataType;
use exface\Core\DataTypes\StringEnumDataType;
use exface\Core\Interfaces\Facades\FacadeInterface;
use exface\Core\Interfaces\WidgetInterface;
use exface\JEasyUIFacade\Facades\Elements\EuiInputSelect;

function regressionSelectOptions($dataType, bool $multiple = false) : string
{
    $select = new class extends EuiInputSelect {
        private $testWidget;

        public function __construct(?WidgetInterface $widget = null, ?FacadeInterface $facade = null) {}

        public function configure($dataType, bool $multiple) : void
        {
            $this->testWidget = new class($dataType, $multiple) {
                private $dataType;
                private $multiple;

                public function __construct($dataType, bool $multiple)
                {
                    $this->dataType = $dataType;
                    $this->multiple = $multiple;
                }

                public function getSelectableOptions() : array { return ['' => 'None'] + $this->dataType->getLabels(); }
                public function getValueDataType() { return $this->dataType; }
                public function getMultiSelect() : bool { return $this->multiple; }
                public function getValues() : array { return []; }
                public function getHeight() { return new class { public function isRelative() { return false; } }; }
            };
        }

        public function getWidget() { return $this->testWidget; }
        protected function isValidationRequired() : bool { return false; }
    };
    $select->configure($dataType, $multiple);
    return $select->buildJsDataOptions();
}
$numberEnum = new class extends NumberEnumDataType { public function __construct() {} };
$numberEnum->setValues([0 => 'Not started', 10 => 'In progress', 101 => 'Completed']);
$numberEnum->setShowValues(false);
$stringEnum = new class extends StringEnumDataType { public function __construct() {} };
$stringEnum->setValues(['OPEN' => 'Open', 'DONE' => 'Review "ready"']);
$stringEnum->setShowValues(false);
$easyui = is_dir(dirname(__DIR__, 4) . '/bower-asset/jeasyui') ? 'bower-asset/jeasyui' : 'salt/jeasyui';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>InputSelect regression</title>
    <link rel="stylesheet" href="../../../../<?= $easyui ?>/themes/default/easyui.css">
    <link rel="stylesheet" href="../../Facades/js/themes/jeasyui.exface.css">
    <script src="../../../../bower-asset/jquery/dist/jquery.min.js"></script>
    <script src="../../../../<?= $easyui ?>/jquery.easyui.min.js"></script>
    <script src="../../Facades/js/jeasyui.facade.js"></script>
</head>
<body>
    <pre id="results">Running...</pre>
    <script>
        function numberSelectOptions() {
            return {<?= regressionSelectOptions($numberEnum) ?>};
        }
        function stringSelectOptions() {
            return {<?= regressionSelectOptions($stringEnum) ?>};
        }
        function multipleSelectOptions() {
            return {<?= regressionSelectOptions($stringEnum, true) ?>};
        }
    </script>
    <script src="input-select.js"></script>
</body>
</html>
