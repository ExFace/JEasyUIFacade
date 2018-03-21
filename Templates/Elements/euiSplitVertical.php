<?php
namespace exface\JEasyUiTemplate\Templates\Elements;

use exface\Core\Exceptions\Templates\TemplateUnsupportedWidgetPropertyWarning;

class euiSplitVertical extends euiContainer
{

    protected function init()
    {
        parent::init();
        $this->setElementType('layout');
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
            $elem = $this->getTemplate()->getElement($panel);
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
                    throw new TemplateUnsupportedWidgetPropertyWarning('The template jEasyUI currently only supports splits with a maximum of 3 panels! "' . $widget->getId() . '" has "' . $widget->countWidgets() . '" panels.');
            }
            $panels_html .= $elem->buildHtml();
        }
        
        return $panels_html;
    }
}