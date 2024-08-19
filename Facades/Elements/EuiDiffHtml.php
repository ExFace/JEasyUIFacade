<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\DataTypes\HtmlDataType;
use exface\Core\Widgets\DiffHtml;
use exface\Core\Widgets\DiffText;

/**
 *
 * @author andrej.kabachnik
 *
 * @method \exface\Core\Widgets\DiffHtml getWidget()
 *
 */
class EuiDiffHtml extends EuiValue
{

    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::buildHtml()
     */
    public function buildHtmlComposite() : string
    {
        $domId = "{$this->getId()}_{$this->getWidget()->getVersionToRender()}";
        $versionToRender = $this->getWidget()->getVersionToRender();

        return <<<HTML
                <div id="{$domId}_shell">
                    <div id="{$domId}" style="padding: 2.5%; outline: 5px solid lightgrey;">
                        {$this->buildHtmlMessage(str_contains($versionToRender, 'diff'))}
                    </div>
                </div>
HTML;
    }

    public function buildHtml()
    {
        $diffClass = DiffText::DIFF_CLASS;
        $output = <<<HTML
                <div style="width:40%; float:left; padding:5%; padding-left: 7.5%; padding-right: 2.5%;">
                    <div id="{$this->getId()}_diff_orig" style="padding: 2.5%; outline: 5px solid lightgrey;">
                        <div class="exf-message" style="text-align:center; background-color: darkgrey; margin-bottom: 30px;">
                            <h1>Original Document</h1>
                        </div>
                    </div>
                </div>
                <div style="width:40%; float:left; padding:5%; padding-left: 2.5%; padding-right: 7.5%;">
                    <div id="{$this->getId()}_diff_diff" class="{$diffClass}" style="padding: 2.5%; outline: 5px solid lightgrey;">
                        <div class="exf-message success" style="text-align:center; margin-bottom: 30px;">
                            <h1>Review Changes</h1>
                        </div>
                    </div>
                </div>
HTML;
        return $output;
    }

    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::buildJs()
     */
    public function buildJsComposite() : string
    {
        $origHtmlJs = $this->escapeString($this->getWidget()->getValue(), true, false);
        $compHtmlJs = $this->escapeString($this->getWidget()->getValueToCompare(), true, false);
        $versionToRender = $this->getWidget()->getVersionToRender();
        $command = "jqThis.append(";
        switch ($versionToRender) {
            case 'old':
                $command .= $origHtmlJs;
                break;
            case 'new':
                $command .= $compHtmlJs;
                break;
            case 'diff':
                $command .= "$(htmldiff(sValue, sValueToCompare))";
                break;
        }
        $command .= ");";
        $css = !str_contains($versionToRender, 'diff') ? '' : "jqThis.addClass(\"difftext-diff\")";

        return <<<JS

                (function() {
                    var sValue = {$origHtmlJs};
                    var sValueToCompare = {$compHtmlJs};
                    var jqThis = $('#{$this->getId()}_{$versionToRender}');
                    {$command}
                    {$css}
                })();
JS;
    }

    public function buildJs()
    {
        $origHtmlJs = $this->escapeString($this->getWidget()->getValue(), true, false);
        $compHtmlJs = $this->escapeString($this->getWidget()->getValueToCompare(), true, false);
        return <<<JS

                (function() {
                    var sValue = {$origHtmlJs};
                    var sValueToCompare = {$compHtmlJs};
                    var jqOrig = $('#{$this->getId()}_diff_orig');
                    //var jqComp = $('#{$this->getId()}_diff_comp');
                    var jqDiff = $('#{$this->getId()}_diff_diff');
                    jqOrig.append({$origHtmlJs});
                    //jqComp.append({$compHtmlJs});
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

    public function buildHtmlMessage(bool $isDiff) : string
    {
        if($isDiff) {
            return <<< HTML
            <div class="exf-message success" style="text-align:center; margin-bottom: 30px;">
                <h1>Review Changes</h1>
            </div>
HTML;
        } else {
            $title = str_contains($this->getWidget()->getVersionToRender(), "old") ?
                "Original Document" : "Current Revision";

            return <<< HTML
            <div class="exf-message" style="text-align:center; background-color: darkgrey; margin-bottom: 30px;">
                <h1>{$title}</h1>
            </div>
HTML;
        }
    }
}