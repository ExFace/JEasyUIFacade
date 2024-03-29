<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Exceptions\Facades\FacadeUnsupportedWidgetPropertyWarning;

class EuiSplitVertical extends EuiContainer
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::getElementType()
     */
    public function getElementType() : ?string
    {
        return 'layout';
    }

    function buildHtml()
    {
        $output = <<<HTML

                    <div class="easyui-layout" id="{$this->getId()}" data-options="fit:true">
                        {$this->buildHtmlForWidgets()}
                    </div>
HTML;
        
        return $output;
    }

    function buildHtmlForWidgets()
    {
        /* @var $widget \exface\Core\Widgets\SplitVertical */
        $widget = $this->getWidget();
        $panels_html = '';
        foreach ($widget->getPanels() as $nr => $panel) {
            $elem = $this->getFacade()->getElement($panel);
            switch ($nr) {
                case 0:
                    $elem->setRegion('north');
                    break;
                case 1:
                    $elem->setRegion('center');
                    break;
                case 2:
                    $elem->setRegion('south');
                    break;
                default:
                    throw new FacadeUnsupportedWidgetPropertyWarning('The facade jEasyUI currently only supports splits with a maximum of 3 panels! "' . $widget->getId() . '" has "' . $widget->countWidgets() . '" panels.');
            }
            $panels_html .= $elem->buildHtml();
        }
        
        return $panels_html;
    }
}