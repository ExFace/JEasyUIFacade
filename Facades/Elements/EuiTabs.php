<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\Tabs;
use exface\Core\Exceptions\Facades\FacadeRuntimeError;
use exface\Core\Widgets\Tab;
use exface\Core\Interfaces\WidgetInterface;

/**
 *
 * @author Andrej Kabachnik
 *        
 * @method Tabs getWidget()
 *        
 */
class EuiTabs extends EuiContainer
{

    private $fit_option = true;

    private $style_as_pills = false;
    
    private $onTabSelectScripts = [];
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::getElementType()
     */
    public function getElementType() : ?string
    {
        return 'tabs';
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiContainer::buildHtml()
     */
    public function buildHtml()
    {
        $widget = $this->getWidget();
        switch ($widget->getVisibility()) {
            case EXF_WIDGET_VISIBILITY_HIDDEN:
                $style = 'visibility: hidden; height: 0px; padding: 0px;';
                break;
            default:
                $style = '';
        }
        $output = <<<HTML
    <div id="{$this->getId()}" style="{$style}" class="{$this->buildCssElementClass()}">
    	{$this->buildHtmlForWidgets()}
    </div>
HTML;
        return $output;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiContainer::buildJs()
     */
    public function buildJs()
    {
         return $this->buildJsTabsInit() . parent::buildJs();
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsTabsInit() : string
    {
        return <<<JS
        
        $('#{$this->getId()}').{$this->getElementType()}({
            {$this->buildJsDataOptions()}
        });
        
JS;
    }

    /**
     * 
     * @return string
     */
    public function buildJsDataOptions()
    {
        $tabPosition = $this->getTabPosition();
        $fit = ($this->getFitOption() ? "fit: true," : "");
        $styleAsPills = ($this->getStyleAsPills() ? "pill: true," : "");
        $tabPosition = $this->getTabPosition();
        $plain = ($tabPosition == 'left' || $tabPosition == 'right' ? 'plain: true,' : '');
        $border = $this->getBorderOption() ? 'true' : 'false';
        
        
        return <<<JS
            tabPosition: '$tabPosition', 
            border: $border,
            $plain
            $fit
            $styleAsPills
            {$this->buildJsDataOptionHeaderWidth()}
            {$this->buildJsDataOptionSelected()}
            {$this->buildJsDataOptionOnSelect()}

JS;
    }
    
    protected function buildJsDataOptionSelected() : string
    {
        $widget = $this->getWidget();
        if ($widget instanceof Tabs) {
            $idx = $widget->getActiveTabIndex();
        } else {
            $idx = 0;
        }
        
        if ($idx > 0) {
            return "selected: $idx,";
        }
        return '';
    }
    
    /**
     * top, bottom, left, right
     * @return string
     */
    protected function getTabPosition() : string
    {
        $pos = strtolower($this->getWidget()->getNavPosition($this->getTabPositionDefault()));
        if (in_array($pos, ['top', 'bottom', 'left', 'right']) === false) {
            throw new FacadeRuntimeError('Invalid tab position "' . $pos . '" for eui-tabs!');
        }
        return $pos;
    }
    
    /**
     *
     * @return string
     */
    protected function getTabPositionDefault() : string
    {
        return 'top';
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsDataOptionHeaderWidth() : string
    {
        return ($this->getWidget()->getHideNavCaptions() ? 'headerWidth: 38,' : '');
    }

    /**
     * 
     * @param bool $value
     * @return \exface\JEasyUIFacade\Facades\Elements\EuiTabs
     */
    public function setFitOption(bool $value)
    {
        $this->fit_option = $value;
        return $this;
    }

    /**
     * 
     * @return bool
     */
    protected function getFitOption() : bool
    {
        return $this->fit_option;
    }
    
    /**
     * 
     * @return bool
     */
    protected function getBorderOption() : bool
    {
        return false;
    }

    /**
     * 
     * @return boolean|\exface\JEasyUIFacade\Facades\Elements\bool
     */
    public function getStyleAsPills()
    {
        return $this->style_as_pills;
    }

    /**
     * 
     * @param bool $style_as_pills
     * @return \exface\JEasyUIFacade\Facades\Elements\EuiTabs
     */
    public function setStyleAsPills(bool $style_as_pills)
    {
        $this->style_as_pills = $style_as_pills;
        return $this;
    }

    /**
     * Returns the default number of columns to layout this widget.
     *
     * @return integer
     */
    public function getNumberOfColumnsByDefault() : int
    {
        return $this->getFacade()->getConfig()->getOption("WIDGET.TABS.COLUMNS_BY_DEFAULT");
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::addOnResizeScript($js)
     */
    public function addOnResizeScript($js)
    {
        foreach ($this->getWidget()->getWidgets() as $tab) {
            $this->getFacade()->getElement($tab)->addOnResizeScript($js);
        }
        return $this;
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsDataOptionOnSelect() : string
    {
        $js = $this->getOnTabSelectScript();
        foreach ($this->getWidget()->getWidgets() as $i => $tab) {
            $script = $this->getOnTabSelectScript($tab);
            if ($script) {
                $js .= <<<JS
                if (index === {$i}) {
                    $script
                }
JS;
            }
        }
        if ($js !== '') {
            $js = <<<JS
            onSelect: function(title, index) {
                $js
            },
JS;
        }
        return $js;
    }
    
    /**
     * 
     * @param int $tabIndex
     * @return string
     */
    protected function getOnTabSelectScript(WidgetInterface $tab = null) : string
    {
        $scripts = $this->onTabSelectScripts[($tab ? $tab->getId() : -1)];
        if ($scripts === null) {
            return '';
        }
        return implode("\n\n", array_unique($scripts));
    }
    
    /**
     * 
     * @param string $js
     * @param int $tabIndex
     * @return EuiTabs
     */
    public function addOnTabSelectScript(string $js, Tab $tab = null) : EuiTabs
    {
        $this->onTabSelectScripts[($tab ? $tab->getId() : -1)][] = $js;
        return $this;
    }
}