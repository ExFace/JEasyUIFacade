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
    public function buildHtml()
    {
        $layout = $this->getWidget()->getLayoutArray();

        return <<<HTML
                <div style="width:40%; float:left; padding:5%; padding-left: 7.5%; padding-right: 2.5%;">
                    {$this->buildHtmlContainer($layout["left"])}
                </div>
                <div style="width:40%; float:left; padding:5%; padding-left: 2.5%; padding-right: 7.5%;">
                    {$this->buildHtmlContainer($layout["right"])}
                </div>
HTML;
    }

    /**
     * {@inheritDoc}
     */
    public function buildJs()
    {
        $origHtmlJs = $this->escapeString($this->getWidget()->getValue(), true, false);
        $compHtmlJs = $this->escapeString($this->getWidget()->getValueToCompare(), true, false);
        $layout = $this->getWidget()->getLayoutArray();
        $cleanSide = str_contains($layout["left"], "diff") ? "right" : "left";
        $cleanHtmlJs = str_contains($layout[$cleanSide], "old") ? $origHtmlJs : $compHtmlJs;

        return <<<JS

                (function() {
                    var sValue = {$origHtmlJs};
                    var sValueToCompare = {$compHtmlJs};
                    var jqClean = $('#{$this->getContainerId($layout[$cleanSide])}');
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
     * @param string $varName
     * @return string
     */
    public function buildHtmlContainer(string $varName) : string
    {
        $varName = strtolower($varName);
        $isDiff = str_contains($varName, 'diff');
        $color = $isDiff ? 'success' : '';
        $diffClass = $isDiff ?  'class="'.DiffText::DIFF_CLASS.'"' : "";
        $title = match (true) {
            $isDiff => "Review Changes",
            str_contains($varName, 'new') => "Revision",
            str_contains($varName, 'old') => "Original",
            default => "",
        };

        return <<< HTML
<div id="{$this->getContainerId($varName)}" {$diffClass} style="padding: 2.5%; outline: 5px solid lightgrey;">
    <div class="exf-message {$color}" style="text-align:center; background-color: darkgrey; margin-bottom: 30px;">
        <h1>{$title}</h1>
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