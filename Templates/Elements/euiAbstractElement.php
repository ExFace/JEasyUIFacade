<?php
namespace exface\JEasyUiTemplate\Templates\Elements;

use exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement;
use exface\JEasyUiTemplate\Templates\JEasyUiTemplate;
use exface\Core\Interfaces\Widgets\iLayoutWidgets;
use exface\Core\Interfaces\Widgets\iFillEntireContainer;

abstract class euiAbstractElement extends AbstractJqueryElement
{

    private $spacing = null;

    private $borderWidth = null;

    public function buildJsInitOptions()
    {
        return '';
    }

    public function buildJsInlineEditorInit()
    {
        return '';
    }

    /**
     *
     * @return JEasyUiTemplate
     */
    public function getTemplate()
    {
        return parent::getTemplate();
    }

    public function escapeString($string)
    {
        return str_replace('"', "'", $string);
    }

    public function prepareData(\exface\Core\Interfaces\DataSheets\DataSheetInterface $data_sheet)
    {
        // apply the formatters
        foreach ($data_sheet->getColumns() as $name => $col) {
            if ($formatter = $col->getFormatter()) {
                $expr = $formatter->toString();
                $function = substr($expr, 1, strpos($expr, '(') - 1);
                $formatter_class_name = 'formatters\'' . $function;
                if (class_exists($class_name)) {
                    $formatter = new $class_name($y);
                }
                
                // See if the formatter returned more results, than there were rows. If so, it was also performed on
                // the total rows. In this case, we need to slice them off and pass to set_column_values() separately.
                // This only works, because evaluating an expression cannot change the number of data rows! This justifies
                // the assumption, that any values after count_rows() must be total values.
                $vals = $formatter->evaluate($data_sheet, $name);
                if ($data_sheet->countRows() < count($vals)) {
                    $totals = array_slice($vals, $data_sheet->countRows());
                    $vals = array_slice($vals, 0, $data_sheet->countRows());
                }
                $data_sheet->setColumnValues($name, $vals, $totals);
            }
        }
        $data = array();
        $data['rows'] = $data_sheet->getRows();
        $data['total'] = $data_sheet->countRowsAll();
        $data['footer'] = $data_sheet->getTotalsRows();
        return $data;
    }

    public function buildJsBusyIconShow()
    {
        return "$.messager.progress({});";
    }

    public function buildJsBusyIconHide()
    {
        return "$.messager.progress('close');";
    }

    public function buildJsShowError($message_body_js, $title_js = null)
    {
        $title_js = ! is_null($title_js) ? $title_js : '"Error"';
        return 'jeasyui_create_dialog($("body"), "' . $this->getId() . '_error", {title: ' . $title_js . ', width: 800, height: "80%"}, ' . $message_body_js . ', true);';
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJsShowMessageSuccess($message_body_js, $title)
     */
    public function buildJsShowMessageError($message_body_js, $title = null)
    {
        $title = ! is_null($title) ? $title : '"' . $this->translate('MESSAGE.ERROR_TITLE') . '"';
        return "$.messager.alert(" . $title . "," . $message_body_js . ",'error');";
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJsShowMessageSuccess($message_body_js, $title)
     */
    public function buildJsShowMessageSuccess($message_body_js, $title = null)
    {
        $title = ! is_null($title) ? $title : "'" . $this->translate('MESSAGE.SUCCESS_TITLE') . "'";
        return "$.messager.show({
					title: " . str_replace('"', '\"', $title) . ",
	                msg: " . $message_body_js . ",
	                timeout:5000,
	                showType:'slide'
	            });";
    }

    /**
     * Returns the masonry-item class name of this widget.
     *
     * This class name is generated from the id of the layout-widget of this widget. Like this
     * nested masonry layouts are possible, because each masonry-container only layout the
     * widgets assigned to it.
     *
     * @return string
     */
    public function getMasonryItemClass()
    {
        $output = '';
        if (($containerWidget = $this->getWidget()->getParentByType('exface\\Core\\Interfaces\\Widgets\\iContainOtherWidgets')) && ($containerWidget instanceof iLayoutWidgets)) {
            $output = $this->getTemplate()->getElement($containerWidget)->getId() . '_masonry_exf-grid-item';
        }
        return $output;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::getWidth()
     */
    public function getWidth()
    {
        $widget = $this->getWidget();
        
        if ($layoutWidget = $widget->getParentByType('exface\\Core\\Interfaces\\Widgets\\iLayoutWidgets')) {
            $columnNumber = $this->getTemplate()->getElement($layoutWidget)->getNumberOfColumns();
        } else {
            $columnNumber = $this->getTemplate()->getConfig()->getOption("COLUMNS_BY_DEFAULT");
        }
        
        $dimension = $widget->getWidth();
        if ($dimension->isRelative()) {
            $cols = $dimension->getValue();
            if ($cols === 'max') {
                $cols = $columnNumber;
            }
            if (is_numeric($cols)) {
                if ($cols < 1) {
                    $cols = 1;
                } else if ($cols > $columnNumber) {
                    $cols = $columnNumber;
                }
                
                if ($cols == $columnNumber) {
                    $output = '100%';
                } else {
                    $output = 'calc(100% * ' . $cols . '/' . $columnNumber . ')';
                }
            } else {
                $output = 'calc(100% * ' . $this->getWidthDefault() . '/' . $columnNumber . ')';
            }
        } elseif ($dimension->isTemplateSpecific() || $dimension->isPercentual()) {
            $output = $dimension->getValue();
        } elseif ($widget instanceof iFillEntireContainer) {
            // Ein "grosses" Widget ohne angegebene Breite fuellt die gesamte Breite des
            // Containers aus.
            $output = '100%';
        } else {
            // Ein "kleines" Widget ohne angegebene Breite hat ist widthDefault Spalten breit.
            $output = 'calc(100% * ' . $this->getWidthDefault() . '/' . $columnNumber . ')';
        }
        return $output;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::getHeight()
     */
    public function getHeight()
    {
        $widget = $this->getWidget();
        
        $dimension = $widget->getHeight();
        if ($dimension->isRelative()) {
            $output = $this->getHeightRelativeUnit() * $dimension->getValue() . 'px';
        } elseif ($dimension->isTemplateSpecific() || $dimension->isPercentual()) {
            $output = $dimension->getValue();
        } elseif ($widget instanceof iFillEntireContainer) {
            // Ein "grosses" Widget ohne angegebene Hoehe fuellt die gesamte Hoehe des
            // Containers aus, ausser es ist nicht alleine in diesem Container.
            $output = '100%';
            if (($containerWidget = $widget->getParentByType('exface\\Core\\Interfaces\\Widgets\\iContainOtherWidgets')) && ($containerWidget->countWidgetsVisible() > 1)) {
                $output = 'auto';
            }
        } else {
            // Ein "kleines" Widget ohne angegebene Hoehe ist heightDefault Einheiten hoch.
            $output = $this->buildCssHeightDefaultValue();
        }
        return $output;
    }

    /**
     * Returns the minimum width of a widget.
     *
     * This is used in the different widgets to determine its min-width and also to calculate
     * the column width for the widget-layout.
     *
     * @return string
     */
    public function getMinWidth()
    {
        if ($this->getWidget() instanceof iLayoutWidgets) {
            // z.B. die Filter-Widgets der DataTables sind genau getWidthRelativeUnits breit und
            // wuerden sonst vom Rand teilweise verdeckt werden.
            $output = ($this->getWidthMinimum() + $this->getSpacing() + 2 * $this->getBorderWidth()) . 'px';
        } else {
            $output = $this->getWidthMinimum() . 'px';
        }
        return $output;
    }

    /**
     * Returns the spacing between two widgets.
     *
     * This is used to calculate the column width for the widget-layout (getMinWidth())
     * and to calculate the padding of the widgets in the layout (getPadding()).
     *
     * @return string
     */
    public function getSpacing()
    {
        if (is_null($this->spacing)) {
            $this->spacing = $this->getTemplate()->getConfig()->getOption("WIDGET.SPACING");
        }
        return $this->spacing;
    }

    /**
     * Returns the padding of a widget in a layout.
     *
     * If the widget is alone in its container there is no padding, so it fills the entire
     * container. Otherwise the padding is calculated from the spacing.
     *
     * @return string
     */
    public function getPadding($default = 0)
    {
        if (($containerWidget = $this->getWidget()->getParentByType('exface\\Core\\Interfaces\\Widgets\\iContainOtherWidgets')) && ($containerWidget->countWidgetsVisible() > 1)) {
            $output = round($this->getSpacing() / 2) . 'px';
        }
        return isset($output) ? $output : $default;
    }

    /**
     * Return the border-width of a widget in a layout.
     *
     * This is used to calculate the column width for the widget-layout (getMinWidth()).
     *
     * @return string
     */
    public function getBorderWidth()
    {
        if (is_null($this->borderWidth)) {
            $this->borderWidth = $this->getTemplate()->getConfig()->getOption("WIDGET.BORDERWIDTH");
        }
        return $this->borderWidth;
    }
    
    /**
     * Wraps the given HTML code in a DIV with properties needed for layouting
     * parent widgets to put this widget in the correct position.
     * 
     * Use the $title parameter to set a title (tooltip) for the gird element.
     *
     * @param string $html
     * @param string $title
     * @return string
     */
    protected function buildHtmlGridItemWrapper($html, $title = '')
    {
        $grid = $this->getWidget()->getParentByType('exface\\Core\\Interfaces\\Widgets\\iLayoutWidgets');
        if ($grid && $grid->countWidgetsVisible() > 1){
            $gridClasses = 'exf-grid-item ' . $this->getMasonryItemClass();
        } else {
            $gridClasses = '';
        }
        
        $style = '';
        if (($padding = $this->getPadding(false)) !== false) {
            $style .= 'padding:' . $padding . ';';
        }
        return <<<HTML
        
            <div title="{$title}" class="{$gridClasses} {$this->buildCssElementClass()}" style="{$style}width:{$this->getWidth()};min-width:{$this->getMinWidth()};height:{$this->getHeight()};box-sizing:border-box;">
                {$html}
            </div>
HTML;
    }
}
?>