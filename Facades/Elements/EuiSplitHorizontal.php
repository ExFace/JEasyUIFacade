<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Exceptions\Facades\FacadeUnsupportedWidgetPropertyWarning;

class EuiSplitHorizontal extends EuiSplitVertical
{

    function buildHtmlForWidgets()
    {
        /* @var $widget \exface\Core\Widgets\SplitHorizontal */
        $widget = $this->getWidget();
        $panels_html = '';
        foreach ($widget->getPanels() as $nr => $panel) {
            $elem = $this->getFacade()->getElement($panel);
            switch ($nr) {
                case 0:
                    $elem->setRegion('west');
                    break;
                case 1:
                    $elem->setRegion('center');
                    break;
                case 2:
                    $elem->setRegion('east');
                    break;
                default:
                    throw new FacadeUnsupportedWidgetPropertyWarning('The facade jEasyUI currently only supports splits with a maximum of 3 panels! "' . $widget->getId() . '" has "' . $widget->countWidgets() . '" panels.');
            }
            $panels_html .= $elem->buildHtml();
        }
        
        return $panels_html;
    }
}