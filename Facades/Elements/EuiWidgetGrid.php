<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryLayoutTrait;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryMasonryGridTrait;
use exface\Core\Widgets\WidgetGrid;

/**
 * The WidgetGrid widget is mapped to a masonry container in jEasyUI
 *
 * @author Andrej Kabachnik
 *        
 * @method WidgetGrid getWidget()
 */
class EuiWidgetGrid extends EuiContainer
{
    use JqueryLayoutTrait;
    use JqueryMasonryGridTrait;
    
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
        
        $minChildWidthValue = $this->getMinChildWidthRelative();
        if ($minChildWidthValue !== 1) {
            $sizerStyle .= "width:calc(100% / {$this->getNumberOfColumns()} * {$minChildWidthValue});";
            $sizerStyle .= "min-width:calc({$this->getMinWidth()} * {$minChildWidthValue});";
        } else {
            $sizerStyle .= "width:calc(100% / {$this->getNumberOfColumns()});";
            $sizerStyle .= "min-width:{$this->getMinWidth()};";
        }
        
        $children_html = <<<HTML
        
                            {$this->buildHtmlForWidgets()}
                            <div id="{$this->getId()}_sizer" style="{$sizerStyle}"></div>

HTML;
                            
        $children_html = $this->buildHtmlGridWrapper($children_html);
                            
        // Hat das Panel eine begrenzte Groesse (es ist nicht alleine in seinem Container)
        // und hat es eine begrenzte Breite (z.B. width: 1), dann ist am Ende seine Hoehe
        // aus irgendeinem Grund um etwa 1 Pixel zu klein, so dass ein Scrollbalken ange-
        // zeigt wird. Aus diesem Grund wird hier dann overflow-y: hidden gesetzt. Falls
        // das Probleme gibt, muss u.U. eine andere Loesung gefunden werden.
        if ($widget->getHeight()->isUndefined() && ($containerWidget = $widget->getParentByType('exface\\Core\\Interfaces\\Widgets\\iContainOtherWidgets')) && ($containerWidget->countWidgetsVisible() > 1)) {
            $styleScript = 'overflow-y:hidden;';
        }
        
        // Wrapper wird gebraucht, denn es wird von easyui neben dem .easyui-panel div
        // ein .panel-header div erzeugt, welches sonst von masonry nicht beachtet wird
        // (beide divs .panel-header und .easyui-panel/.panel-body werden unter einem
        // .panel div zusammengefasst).
        $output = <<<HTML
                            
                <div class="easyui-{$this->getElementType()}"
                            id="{$this->getId()}"
                            data-options="{$this->buildJsDataOptions()}"
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
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::buildHtmlGridItemWrapper()
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
        
        if ($this->getNumberOfColumns() > $this->getMinChildWidthRelative()) {
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
        
        $onLoadScript = $this->getOnLoadScript() ? ', onLoad: function(){' . $this->getOnLoadScript() . '}' : '';
        $onResizeScript = $this->getOnResizeScript() ? ', onResize: function(){' . $this->getOnResizeScript() . '}' : '';
        
        // A standalone panel will always fill out the parent container (fit: true), but
        // other widgets based on a panel may not do so. Thus, the fit data-option is added
        // here, in the generate_html() method, which is verly likely to be overridden in
        // extending classes!
        $fit = $this->getFitOption() ? ', fit: true' : '';
        
        return ltrim($onLoadScript . $onResizeScript . $fit, ", ");
    }
    
    public function setFitOption(int $value)
    {
        $this->fit_option = $value;
        return $this;
    }
    
    protected function getFitOption()
    {
        return $this->fit_option;
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

    public function inheritsNumberOfColumns() : bool
    {
        return true;
    }
    
    public function getNumberOfColumnsByDefault() : int
    {
        return $this->getFacade()->getConfig()->getOption("WIDGET.PANEL.COLUMNS_BY_DEFAULT");
    }
}
?>