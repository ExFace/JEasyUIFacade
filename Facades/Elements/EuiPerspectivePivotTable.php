<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Interfaces\DataSheets\DataSheetInterface;
use exface\Core\Interfaces\DataTypes\EnumDataTypeInterface;
use exface\Core\Interfaces\WidgetInterface;
use exface\Core\Widgets\PivotTable;
use exface\JEasyUIFacade\Facades\JEasyUIFacade;
use exface\JEasyUIFacade\Facades\Elements\Traits\EuiDataElementTrait;
use exface\Core\Facades\AbstractAjaxFacade\Elements\PerspectiveTrait;

/**
 * Renders PivotTable widgets with Perspective.
 *
 * @author andrej.kabachnik
 *
 */
class EuiPerspectivePivotTable extends EuiData
{
    use EuiDataElementTrait;

    use PerspectiveTrait;

    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiDataTable::buildHtml()
     */
    public function buildHtml()
    {
        return $this->buildHtmlPanelWrapper($this->buildHtmlPerspective());
    }

    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiDataTable::buildJs()
     */
    public function buildJs()
    {
        return <<<JS

    setTimeout(function() {
        {$this->buildJsRefresh()}
        setTimeout(function(){
            {$this->buildJsResize()}
        }, 100);
    }, 0);
    {$this->buildJsForPanel()}
    {$this->buildJsDataLoadFunction()}

JS;
    }

    /**
     *
     * @see EuiDataElementTrait::buildJsDataLoaderOnLoaded()
     */
    protected function buildJsDataLoaderOnLoaded(string $dataJs): string
    {
        return $this->buildJsPerspectiveRender($dataJs . '.pivotdata');
    }

    /**
     * Returns JavaScript and CSS headers, needed for the element as an array of lines.
     *
     * See trait docs above for details!
     *
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildHtmlHeadTags()
     */
    public function buildHtmlHeadTags()
    {
        $includes = parent::buildHtmlHeadTags();
        return array_merge($includes, $this->buildHtmlHeadTagsForPerspective());
    }

    /**
     *
     * @return string
     */
    protected function buildJsResize() : string
    {
        return $this->buildJsResizeInnerWidget() . "if (typeof document.getElementById('{$this->getId()}')?.resize === 'function') { document.getElementById('{$this->getId()}').resize(); }";
    }

    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiDataTable::getElementType()
     */
    public function getElementType() : ?string
    {
        return null;
    }

    /**
     * A pivot grid expects data in a different format: [ {field: value, ...}, {...}, ...]
     *
     * @return array
     */
    public static function buildResponseData(JEasyUIFacade $facade, DataSheetInterface $data_sheet, WidgetInterface $widget)
    {
        $data = array();
        $colFormatters = [];
        $colCaptions = [];
        /** @var PivotTable $widget */
        foreach ($data_sheet->getColumns() as $col) {
            $colWidget = $widget->getColumnByDataColumnName($col->getName());
            if (! $colWidget) {
                continue;
            }
            $colCaptions[$col->getName()] = $colWidget->getCaption();
            $colType = $col->getDataType();
            switch (true) {
                case $colType instanceof EnumDataTypeInterface:
                    $colFormatters[$col->getName()] = $colType;
                    break;
            }
        }

        foreach ($data_sheet->getRows() as $row_nr => $row) {
            foreach ($row as $fld => $val) {
                if ($colCaption = $colCaptions[$fld] ?? null) {
                    if (null !== $colType = ($colFormatters[$fld] ?? null)) {
                        $val = $colType->format($val);
                    }
                    $data[$row_nr][$colCaption] = $val;
                }
            }
        }

        return [
            'pivotdata' => $data
        ];
    }
}