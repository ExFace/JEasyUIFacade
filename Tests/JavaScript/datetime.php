<?php
require dirname(__DIR__, 4) . '/autoload.php';

use exface\Core\Facades\AbstractAjaxFacade\Formatters\JsDateFormatter;
use exface\Core\Interfaces\Facades\FacadeInterface;
use exface\Core\Interfaces\WidgetInterface;
use exface\JEasyUIFacade\Facades\Elements\EuiInputDateTime;
use exface\JEasyUIFacade\Facades\Elements\EuiDataTable;

// Exercise the generated options without requiring a workbench or database.
$element = new class extends EuiInputDateTime {
    public function __construct(?WidgetInterface $widget = null, ?FacadeInterface $facade = null) {}

    public function getId()
    {
        return 'datetime-regression';
    }

    public function translate($message_id, array $placeholders = [], $number_for_plurification = null)
    {
        return 'Invalid datetime';
    }

    protected function getDateFormatter()
    {
        return new class extends JsDateFormatter {
            public function __construct()
            {
                $this->setFormat('dd.MM.yyyy HH:mm:ss');
            }

            protected function buildJsDateFormatInternal()
            {
                return 'yyyy-MM-dd HH:mm:ss';
            }
        };
    }

    protected function buildJsValueFormatter($jsInput)
    {
        return "exfTools.date.format({$jsInput}, 'dd.MM.yyyy HH:mm:ss')";
    }
};
$table = new class extends EuiDataTable {
    public function __construct(?WidgetInterface $widget = null, ?FacadeInterface $facade = null) {}

    protected function buildJsonOnLoadSuccessSelectionFix(string $selfJs = 'jqSelf') : string
    {
        return '';
    }

    public function buildTestLoadOptions() : string
    {
        return $this->buildJsOnLoadSuccessOption();
    }
};
$table->setEditable(true);
$table->addOnLoadSuccess($table->buildJsEditModeEnabler());
$table->addOnLoadSuccess(<<<'JS'
    jqSelf.triggerHandler('exf-editors-ready');
    jqSelf.datagrid('getPanel').find('.datagrid-editable-input').on('change.batchRegression', function() {
        jqSelf.triggerHandler('exf-batch-change');
    });
JS);
$easyui = is_dir(dirname(__DIR__, 4) . '/bower-asset/jeasyui') ? 'bower-asset/jeasyui' : 'salt/jeasyui';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Datetime regression</title>
    <link rel="stylesheet" href="../../../../<?= $easyui ?>/themes/default/easyui.css">
    <link rel="stylesheet" href="../../Facades/js/themes/jeasyui.exface.css">
    <script src="../../../../bower-asset/jquery/dist/jquery.min.js"></script>
    <script src="../../../../npm-asset/moment/min/moment.min.js"></script>
    <script src="../../../core/Facades/AbstractAjaxFacade/js/exfTools.js"></script>
    <script src="../../../../<?= $easyui ?>/jquery.easyui.min.js"></script>
    <script src="../../Facades/js/jeasyui.facade.js"></script>
</head>
<body>
    <pre id="results">Running...</pre>
    <script>
        function datetimeOptions() {
            return {<?= $element->buildJsInitOptions() ?>};
        }
        function clearDatetime(input) {
            input.<?= $element->buildJsValueSetterMethod('null') ?>;
        }
        function batchedGridOptions() {
            return {test: true <?= $table->buildTestLoadOptions() ?>};
        }
    </script>
    <script src="datetime.js"></script>
</body>
</html>
