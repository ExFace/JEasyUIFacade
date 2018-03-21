<?php
namespace exface\JEasyUiTemplate\Templates\Elements;

use exface\Core\Exceptions\Templates\TemplateUnsupportedWidgetPropertyWarning;

class euiSplitHorizontal extends euiSplitVertical
{

    function buildHtmlForWidgets()
    {
        /* @var $widget \exface\Core\Widgets\SplitHorizontal */
        $widget = $this->getWidget();
        $panels_html = '';
        foreach ($widget->getPanels() as $nr => $panel) {
            $elem = $this->getTemplate()->getElement($panel);
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
                    throw new TemplateUnsupportedWidgetPropertyWarning('The template jEasyUI currently only supports splits with a maximum of 3 panels! "' . $widget->getId() . '" has "' . $widget->countWidgets() . '" panels.');
            }
            $panels_html .= $elem->buildHtml();
        }
        
        return $panels_html;
    }
}