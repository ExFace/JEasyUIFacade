<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement;
use exface\JEasyUIFacade\Facades\JEasyUIFacade;
use exface\Core\Interfaces\Widgets\iLayoutWidgets;
use exface\Core\Interfaces\Widgets\iFillEntireContainer;
use exface\Core\Interfaces\Widgets\iContainOtherWidgets;

abstract class EuiAbstractElement extends AbstractJqueryElement
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
     * @return JEasyUIFacade
     */
    public function getFacade()
    {
        return parent::getFacade();
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
        return 'jeasyui_show_error(' . $title_js . ', ' . $message_body_js . ', "' . $this->getId() . '");';
    }
    
    public function buildJsShowErrorAjax(string $jqXHR) : string
    {
        return <<<JS

        switch ($jqXHR.status) {
            case 0: 
                var sError = JSON.stringify({
                    error: {
                        type: 'ERROR ',
                        code: '7CX9G68',
                        title: "{$this->translate('ERROR.NO_CONNECTION')}",
                        message: "{$this->translate('ERROR.NO_CONNECTION_HINT')}"
                    }
                });
                {$this->buildJsShowError('sError')}
                break;
            default:  
                {$this->buildJsShowError("$jqXHR.responseText", "$jqXHR.status + ' ' + $jqXHR.statusText")}   
        }

JS;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsShowMessageSuccess($message_body_js, $title)
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
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsShowMessageSuccess($message_body_js, $title)
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
        if (($containerWidget = $this->getWidget()->getParentByClass('exface\\Core\\Interfaces\\Widgets\\iContainOtherWidgets')) && ($containerWidget instanceof iLayoutWidgets)) {
            $output = $this->getFacade()->getElement($containerWidget)->getId() . '_masonry_exf-grid-item';
        }
        return $output;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::getWidth()
     */
    public function getWidth()
    {
        $widget = $this->getWidget();
        
        if ($layoutWidget = $widget->getParentByClass('exface\\Core\\Interfaces\\Widgets\\iLayoutWidgets')) {
            $columnNumber = $this->getFacade()->getElement($layoutWidget)->getNumberOfColumns();
        } else {
            $columnNumber = $this->getFacade()->getConfig()->getOption("WIDGET.ALL.COLUMNS_BY_DEFAULT");
        }
        
        $dimension = $widget->getWidth();
        if ($dimension->isRelative()) {
            $cols = $dimension->getValue();
            if ($cols === 'max') {
                $cols = $columnNumber;
            }
            if (is_numeric($cols)) {
                /*if ($cols < 1) {
                    $cols = 1;
                } else */if ($cols > $columnNumber) {
                    $cols = $columnNumber;
                }
                
                if ($cols == $columnNumber) {
                    $output = '100%';
                } else {
                    $output = 'calc(100% * ' . $cols . ' / ' . $columnNumber . ')';
                }
            } else {
                $output = 'calc(100% * ' . $this->getWidthDefault() . ' / ' . $columnNumber . ')';
            }
        } elseif ($dimension->isFacadeSpecific() || $dimension->isPercentual()) {
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
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::getHeight()
     */
    public function getHeight()
    {
        $widget = $this->getWidget();
        
        $dimension = $widget->getHeight();
        if ($dimension->isMax()) {
            if ($containerWidget = $widget->getParentByClass(iContainOtherWidgets::class)) {
                $this->getFacade()->getElement($containerWidget)->addOnResizeScript($this->buildJsEuiSetHeigthMax($containerWidget));
                $output = $this->buildCssHeightDefaultValue();
            } else {
                $output = '100%';
            }                        
        } elseif ($dimension->isRelative()) {
            $output = $this->getHeightRelativeUnit() * $dimension->getValue() . 'px';
        } elseif ($dimension->isFacadeSpecific() || $dimension->isPercentual()) {
            $output = $dimension->getValue();
        } elseif ($widget instanceof iFillEntireContainer) {
            // Ein "grosses" Widget ohne angegebene Hoehe fuellt die gesamte Hoehe des
            // Containers aus, ausser es ist nicht alleine in diesem Container.
            $output = '100%';
            if (($containerWidget = $widget->getParentByClass('exface\\Core\\Interfaces\\Widgets\\iContainOtherWidgets')) && ($containerWidget->countWidgetsVisible() > 1)) {
                $output = 'auto';
            }
        } else {
            // Ein "kleines" Widget ohne angegebene Hoehe ist heightDefault Einheiten hoch.
            $output = $this->buildCssHeightDefaultValue();
        }
        return $output;
    }
    
    /**
     * Building the js script to change the height of an element to the maximum free value.
     *
     * @param iContainOtherWidgets $containerWidget
     * @param string $gridItemCssClass
     * @param string $onChangeHeightJs
     * @return string
     */
    protected function buildJsSetHeightMax(iContainOtherWidgets $containerWidget, string $gridItemCssClass, string $onChangeHeightJs) : string
    {
        $count = 0;
        $js = <<<JS
        //console.log('Calculated Height: {$this->getId()}');
        var yCoords = new Array();
        var parElem;
        var contElem = $('#{$this->getFacade()->getElement($containerWidget)->getId()}');
        var surElem = $('#{$this->getId()}').parent().closest('.{$gridItemCssClass}').first();
        
        // if no container element or no top element for element to change height for is found, or the contElem is the top element dont do anything
        if (contElem.length == 0 || surElem.length == 0 || contElem[0] == surElem[0]) {
            return;
        }
JS;
        foreach ($containerWidget->getChildren() as $child) {
            if ($child->isHidden()) {
                continue;
            }
            if ($child->getHeight()->isMax()) {
                $count++;
            }
            $js.= <<<JS

        (function(){
            var elem = $('#{$this->getFacade()->getElement($child)->getId()}');
            var yElemCord;
            // add bottom y-Coord of element to array
            if (elem.length > 0 && elem.parents('#{$this->getFacade()->getElement($containerWidget)->getId()}').length > 0) {
                parElem = elem.parent().closest('.{$gridItemCssClass}').first();
                if (parElem.length > 0 && parElem[0] !== contElem[0]) {
                    elem = parElem;
                }
                // Skip elements that are not above or below the max'ed element
                // usage of Math.round() because it can occur that masonry grid somehow overlaps the elements within 1px (happened with a Markdown editor next to a widget group)
                // this would lead to the new element height not be calculated correctly for at least the first round of calculation
                if (Math.round(elem.offset().left + elem.outerWidth(true)) <= Math.round(surElem.offset().left) || Math.round(elem.offset().left) >= Math.round(surElem.offset().left + surElem.outerWidth(true))) {
                    return;
                }
                yElemCord = elem.offset().top + elem.outerHeight(true);            
                yCoords.push(yElemCord);
            }
        })();

JS;
        }
        
        $js .= <<<JS
        // get max y-Coord of all visible elements
        yCoords.sort((a,b)=>(b-a));
        var yMax = yCoords[0];
        
        // get height and top y-Coord of cointainer Widget
        var contHeight = contElem.height();
        var yContTop = contElem.offset().top;
        
        // get current and default height of element with height 'max'
        var elemHeight = surElem.outerHeight(true);
        var elemDefaultHeight = '{$this->buildCssHeightDefaultValue()}';
        elemDefaultHeight = elemDefaultHeight.substr(0, elemDefaultHeight.length-2);
        elemDefaultHeight = parseInt(elemDefaultHeight);

        // get widgets in container widgets with height 'max'        
        var countMaxElem = {$count};

        // if the currently last element is at the bottom of the container dont change heights
        if ((yMax - yContTop) == contHeight) {
            return;
        }

        // if the container height gets reduced and last element is at the bottom, reduces sizes
        if ((yMax - yContTop) > contHeight) {
            var heightSubst = yMax - yContTop - contHeight;
            var newHeight = elemHeight - heightSubst / countMaxElem;
            newHeight = Math.floor(newHeight);
            if (newHeight < elemDefaultHeight) {
                newHeight = elemDefaultHeight;
            }

        // if there is free space in the container, calculate new height of the element
        } else {
            var newHeight = contHeight - (yMax - yContTop);
            newHeight = newHeight / countMaxElem + elemHeight;
            newHeight = Math.floor(newHeight);
        }

        // if the new height calculated is the same as the current element height, dont set its new height
        if (newHeight == elemHeight) {
            return;
        }
      
        setTimeout(function(){
            var oldHeight = surElem.outerHeight(true);
            var heightDiff = Math.abs(oldHeight - newHeight);
            // only change the height when the difference between old and new height is atleast 10px
            // thats necessary because of the fix regarding the container scroll
            // if height gets changed every time its not the old height that could lead to an idefitine number of height changes
            if (heightDiff <= 5) {
                return;
            }                       
            surElem.outerHeight(newHeight,true);
            {$onChangeHeightJs}                 
            $('#{$this->getId()}').resize();  
        },0);
JS;
        return <<<JS
    setTimeout(function() {
        $js
    },0);
JS;
    }
    
    protected function buildJsEuiSetHeigthMax(iContainOtherWidgets $containerWidget, string $onChangeHeightJs = '') : string
    {
        if ($this->getFacade()->getElement($containerWidget) instanceof EuiWidgetGrid) {
            $onChangeHeightJs .= $this->getFacade()->getElement($containerWidget)->buildJsLayouter();
            // Double check that we really did not force the container to scroll (may happen with masonry)
            // If so, decrease the max'ed height to fit without scrolling!
            $onChangeHeightJs .= <<<JS

            setTimeout(function(){
                var diff = contElem[0].scrollHeight - contHeight;
                // check if diff is bigger than 0 but smaller than 5 because that means the container scroll most likely
                // was cause by this element and not my others in the same container
                if (diff > 0 && diff < 5) {
                    surElem.outerHeight(newHeight - diff, true);
                    {$onChangeHeightJs}
                }
            }, 0);

JS;
        }
        return $this->buildJsSetHeightMax($containerWidget, 'exf-element', $onChangeHeightJs);
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
            $singleColumnWidth = $this->getFacade()->getConfig()->getOption('WIDGET.ALL.WIDTH_MINIMUM');
            $output = ($singleColumnWidth + $this->getSpacing() + 2 * $this->getBorderWidth()) . 'px';
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
            $this->spacing = $this->getFacade()->getConfig()->getOption("WIDGET.SPACING");
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
        if (($containerWidget = $this->getWidget()->getParentByClass('exface\\Core\\Interfaces\\Widgets\\iContainOtherWidgets')) && ($containerWidget->countWidgetsVisible() > 1)) {
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
            $this->borderWidth = $this->getFacade()->getConfig()->getOption("WIDGET.BORDERWIDTH");
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
        $widget = $this->getWidget();
        $grid = $widget->getParentByClass('exface\\Core\\Interfaces\\Widgets\\iLayoutWidgets');
        if ($grid && $grid->countWidgetsVisible() > 1){
            $gridClasses = 'exf-grid-item ' . $this->getMasonryItemClass();
        } else {
            $gridClasses = '';
        }
        
        $style = '';
        
        // Padding
        if (($padding = $this->getPadding(false)) !== false) {
            $style .= ' padding:' . $padding . ';';
        }
        
        $width = $widget->getWidth();
        if ($width->isUndefined() === true || $width->isRelative() === true) {
            $style .= " min-width: {$this->getMinWidth()};";
        }
        
        return <<<HTML
        
            <div title="{$title}" class="{$gridClasses} {$this->buildCssElementClass()}" style="{$style}width:{$this->getWidth()};height:{$this->getHeight()};box-sizing:border-box;">
                {$html}
            </div>
HTML;
    }
         
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJs()
     */
    public function buildJs()
    {
        return '';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildHtml()
     */
    public function buildHtml()
    {
        return '';
    }
    
    /**
     * Returns an inline JS-snippet, that resolves to TRUE if the jEasyUI control for this element is
     * already initialized in FALSE otherwise.
     * 
     * @return string
     */
    public function buildJsCheckInitialized() : string
    {
        return "($('{$this->getId()}').data('{$this->getElementType()}') !== undefined)";
    }
}
?>