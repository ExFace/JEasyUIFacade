<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\DiffText;

/**
 * JEasyUI implementation of the corresponding widget.
 *
 * @author Georg Bieger
 */
class EuiDiffHtml extends EuiValue
{
    /**
     * {@inheritDoc}
     */
    public function buildHtml() : string
    {
        $widget = $this->getWidget();

        return <<<HTML
                <div style="width:40%; float:left; padding:2.5%; padding-left: 7.5%;">
                    {$this->buildHtmlContainer($widget, "left")}
                </div>
                <div style="width:40%; float:left; padding:2.5%; padding-right: 7.5%;">
                    {$this->buildHtmlContainer($widget, "right")}
                </div>
HTML;
    }

    /**
     * {@inheritDoc}
     */
    public function buildJs()
    {
        $widget = $this->getWidget();
        $origHtmlJs = $this->escapeString($widget->getValue(), true, false);
        $compHtmlJs = $this->escapeString($widget->getValueToCompare(), true, false);
        $cleanSide = !str_contains($widget->getRenderedVersion("left"), "diff") ? "left" : "right";
        $cleanHtmlJs = str_contains($widget->getRenderedVersion($cleanSide), "old") ? $origHtmlJs : $compHtmlJs;

        return <<<JS

                (function() {
                    var sValue = {$origHtmlJs};
                    var sValueToCompare = {$compHtmlJs};
                    var jqClean = $('#{$this->getContainerId($widget->getRenderedVersion($cleanSide))}');
                    var jqDiff = $('#{$this->getcontainerId("diff")}');
                    jqClean.append({$cleanHtmlJs});
                    jqDiff.append($(htmldiff(sValue, sValueToCompare)));
                })();
JS;
    }

    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildHtmlHeadTags()
     */
    public function buildHtmlHeadTags()
    {
        return array(
            '<script type="text/javascript" src="' . $this->getFacade()->buildUrlToSource('LIBS.HTMLDIFF.JS') . '"></script>'
        );
    }

    /**
     * Generates a container depending on the corresponding layout.
     *
     * The container holds a title card and identifies whether it should be filled with the original, the revision
     * or display the detected changes between the two.
     *
     * @param DiffText $widget
     * @param string $side
     * @return string
     */
    public function buildHtmlContainer(DiffText $widget, string $side) : string
    {
        $renderedVersion = $widget->getRenderedVersion($side);
        $diffClass = str_contains($renderedVersion, 'diff') ?  'class="'.DiffText::DIFF_CLASS.'"' : "";
        return <<< HTML
<div id="{$this->getContainerId($renderedVersion)}" {$diffClass} style="padding: 2.5%; outline: 5px solid lightgrey;">
    <div class="exf-message {$widget->getTitleColor($side, true)}" style="text-align:center; background-color: darkgrey; margin-bottom: 30px;">
        <h1>{$widget->getTitle($side)}</h1>
    </div>
</div>
HTML;
    }

    /**
     * Generates a meaningful ID to consistently query divs.
     *
     * @param $varName
     * @return string
     */
    public function getContainerId($varName) : string
    {
        return "{$this->getId()}_htmlDiff_{$varName}";
    }
}