<?php
namespace exface\JEasyUiTemplate\Templates\Elements;

use exface\Core\Widgets\Panel;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryLayoutInterface;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryLayoutTrait;
use exface\Core\DataTypes\BooleanDataType;

/**
 * The Panel widget is mapped to a panel in jEasyUI
 *
 * @author Andrej Kabachnik
 *        
 * @method Panel getWidget()
 */
class euiPanel extends euiContainer implements JqueryLayoutInterface
{
    
    use JqueryLayoutTrait;

    private $on_load_script = '';

    private $on_resize_script = '';
    
    private $fit_option = true;

    protected function init()
    {
        parent::init();
        $this->setElementType('panel');
    }

    public function buildHtml()
    {
        $widget = $this->getWidget();
        
        switch ($widget->getVisibility()){
            case EXF_WIDGET_VISIBILITY_HIDDEN:
                $style = 'visibility: hidden; height: 0px; padding: 0px;';
                break;
            default:
                $style = '';
                
        }
        
        $title = $widget->getHideCaption() ? '' : ' title="' . $widget->getCaption() . '"';
        
        $children_html = <<<HTML
        
                            {$this->buildHtmlForWidgets()}
                            <div id="{$this->getId()}_sizer" style="width:calc(100% / {$this->getNumberOfColumns()});min-width:{$this->getMinWidth()};"></div>
HTML;
        
        // Wrap children widgets with a grid for masonry layouting - but only if there is something to be layed out
        // Normalerweise wird das der masonry-wrapper nicht gebraucht. Masonry ordnet
        // dann die Elemente an und passt direkt die Grosse des Panels an den neuen Inhalt an.
        // Nur wenn das Panel den gesamten Container ausfuellt, darf seine Groesse nicht
        // geaendert werden. In diesem Fall wird der wrapper eingefuegt und stattdessen seine
        // Groesse geaendert. Dadurch wird der Inhalt scrollbar im Panel angezeigt.
        if ((is_null($widget->getParent()) || (($containerWidget = $widget->getParentByType('exface\\Core\\Interfaces\\Widgets\\iContainOtherWidgets')) && ($containerWidget->countWidgetsVisible() == 1))) && ($widget->countWidgetsVisible() > 1)) {
            $children_html = <<<HTML

                        <div class="grid" id="{$this->getId()}_masonry_grid" style="width:100%;height:100%;">
                            {$children_html}
                        </div>
HTML;
        }
        
        // Hat das Panel eine begrenzte Groesse (es ist nicht alleine in seinem Container)
        // und hat es eine begrenzte Breite (z.B. width: 1), dann ist am Ende seine Hoehe
        // aus irgendeinem Grund um etwa 1 Pixel zu klein, so dass ein Scrollbalken ange-
        // zeigt wird. Aus diesem Grund wird hier dann overflow-y: hidden gesetzt. Falls
        // das Probleme gibt, muss u.U. eine andere Loesung gefunden werden.
        if ($widget->getHeight()->isUndefined() && ($containerWidget = $widget->getParentByType('exface\\Core\\Interfaces\\Widgets\\iContainOtherWidgets')) && ($containerWidget->countWidgetsVisible() > 1)) {
            $styleScript = 'overflow-y:hidden;';
        }
        
        // A standalone panel will always fill out the parent container (fit: true), but
        // other widgets based on a panel may not do so. Thus, the fit data-option is added
        // here, in the generate_html() method, which is verly likely to be overridden in
        // extending classes!
        $fit = $this->getFitOption() ? ', fit: true' : '';
        
        // Wrapper wird gebraucht, denn es wird von easyui neben dem .easyui-panel div
        // ein .panel-header div erzeugt, welches sonst von masonry nicht beachtet wird
        // (beide divs .panel-header und .easyui-panel/.panel-body werden unter einem
        // .panel div zusammengefasst).
        // Fit:true wird gebraucht, denn sonst aendert das Panel seine Groesse nicht mehr
        // wenn sich die Groesse des Bildschirms/Containers aendert.
        $output = <<<HTML

                <div class="easyui-{$this->getElementType()}"
                            id="{$this->getId()}"
                            data-options="{$this->buildJsDataOptions()}{$fit}"
                            {$title}
                            style="{$styleScript}">
                        {$children_html}
                </div>
HTML;
                        
        if ($this->isOnlyVisibleElementInContainer()) {
            $output = $this->buildHtmlGridItemWrapper($output, $style);
        }
        
        return $output;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiAbstractElement::buildHtmlGridItemWrapper()
     */
    protected function buildHtmlGridItemWrapper($html, $style = '')
    {
        return <<<HTML

            <div class="exf-grid-item {$this->getMasonryItemClass()} {$this->buildCssElementClass()}" style="width:{$this->getWidth()};min-width:{$this->getMinWidth()};height:{$this->getHeight()};padding:{$this->getPadding()};box-sizing:border-box;{$style}">
                {$html}
            </div>

HTML;
    }
    
    public function getHeight()
    {
        $dimension = $this->getWidget()->getHeight();
        if ($dimension->isUndefined() || $dimension->isMax()) {
            return 'auto';
        }
        
        return parent::getHeight();
    }

    public function buildJs()
    {
        $output = parent::buildJs();
        
        // Layout-Funktion hinzufuegen
        $output .= $this->buildJsLayouterFunction();
        
        return $output;
    }

    /**
     * Generates the contents of the data-options attribute (e.g.
     * iconCls, collapsible, etc.)
     *
     * @return string
     */
    public function buildJsDataOptions()
    {
        /** @var Panel $widget */
        $widget = $this->getWidget();
        
        if ($widget->getNumberOfColumns() != 1) {
            $this->addOnLoadScript($this->buildJsLayouter() . ';');
            // The resize-script seems to get called too early sometimes if the 
            // panel is loaded via AJAX, so we need to add a timeout if the
            // laouter function is not defined yet. This was preventing error 
            // widgets from AJAX requests to be shown if loading an editor with
            // a corrupted attribute_alias. Just using setTimeout() every time
            // is not an option either as it introduces a visible delay in those
            // cases, when a direct call would have worked.
            $this->addOnResizeScript('
                try {
                    ' . $this->buildJsLayouter() . '
                } catch (e) {
                    setTimeout(function(){' . $this->buildJsLayouter() . '}, 0);
                }');
        }
        $collapsibleScript = 'collapsible: ' . ($widget->isCollapsible() ? 'true' : 'false');
        $iconClassScript = $widget->getIcon() ? ', iconCls:\'' . $this->buildCssIconClass($widget->getIcon()) . '\'' : '';
        $onLoadScript = $this->getOnLoadScript() ? ', onLoad: function(){' . $this->getOnLoadScript() . '}' : '';
        $onResizeScript = $this->getOnResizeScript() ? ', onResize: function(){' . $this->getOnResizeScript() . '}' : '';
                
        return $collapsibleScript . $iconClassScript . $onLoadScript . $onResizeScript;
    }
    
    public function setFitOption($value)
    {
        $this->fit_option = BooleanDataType::cast($value);
        return $this;
    }
    
    public function getFitOption()
    {
        return $this->fit_option;
    }

    public function buildHtmlHeadTags()
    {
        $includes = parent::buildHtmlHeadTags();
        $includes[] = '<script type="text/javascript" src="exface/vendor/bower-asset/masonry/dist/masonry.pkgd.min.js"></script>';
        return $includes;
    }

    public function getOnLoadScript()
    {
        return $this->on_load_script;
    }

    public function addOnLoadScript($value)
    {
        $this->on_load_script .= $value;
        return $this;
    }

    public function getOnResizeScript()
    {
        return $this->on_resize_script;
    }

    public function addOnResizeScript($value)
    {
        $this->on_resize_script .= $value;
        return $this;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryLayoutInterface::buildJsLayouterFunction()
     */
    public function buildJsLayouterFunction()
    {
        $widget = $this->getWidget();
        
        // Auch das Layout des Containers wird erneuert nachdem das eigene Layout aktualisiert
        // wurde.
        $layoutWidgetScript = '';
        if ($layoutWidget = $widget->getParentByType('exface\\Core\\Interfaces\\Widgets\\iLayoutWidgets')) {
            $layoutWidgetScript = <<<JS
{$this->getTemplate()->getElement($layoutWidget)->buildJsLayouter()};
JS;
        }
        
        if ((is_null($widget->getParent()) || (($containerWidget = $widget->getParentByType('exface\\Core\\Interfaces\\Widgets\\iContainOtherWidgets')) && ($containerWidget->countWidgetsVisible() == 1))) && ($widget->countWidgetsVisible() > 1)) {
            // Wird ein masonry_grid-wrapper hinzugefuegt, sieht die Layout-Funktion etwas
            // anders aus als wenn der wrapper fehlt. Siehe auch oben in buildHtml().
            $output = <<<JS

    function {$this->buildJsFunctionPrefix()}layouter() {
        if (!$("#{$this->getId()}_masonry_grid").data("masonry")) {
            if ($("#{$this->getId()}_masonry_grid").find(".{$this->getId()}_masonry_exf-grid-item").length > 0) {
                $("#{$this->getId()}_masonry_grid").masonry({
                    columnWidth: "#{$this->getId()}_sizer",
                    itemSelector: ".{$this->getId()}_masonry_exf-grid-item"
                });
            }
        } else {
            $("#{$this->getId()}_masonry_grid").masonry("reloadItems");
            $("#{$this->getId()}_masonry_grid").masonry();
        }
        {$layoutWidgetScript}
    }
JS;
        } else {
            $output = <<<JS

    function {$this->buildJsFunctionPrefix()}layouter() {
        if (!$("#{$this->getId()}").data("masonry")) {
            if ($("#{$this->getId()}").find(".{$this->getId()}_masonry_exf-grid-item").length > 0) {
                $("#{$this->getId()}").masonry({
                    columnWidth: "#{$this->getId()}_sizer",
                    itemSelector: ".{$this->getId()}_masonry_exf-grid-item"
                });
            }
        } else {
            $("#{$this->getId()}").masonry("reloadItems");
            $("#{$this->getId()}").masonry();
        }
        {$layoutWidgetScript}
    }
JS;
        }
        
        return $output;
    }

    /**
     * Returns the default number of columns to layout this widget.
     *
     * @return integer
     */
    public function getDefaultColumnNumber()
    {
        return $this->getTemplate()->getConfig()->getOption("WIDGET.PANEL.COLUMNS_BY_DEFAULT");
    }

    /**
     * Returns if the the number of columns of this widget depends on the number of columns
     * of the parent layout widget.
     *
     * @return boolean
     */
    public function inheritsColumnNumber()
    {
        return true;
    }
}
?>