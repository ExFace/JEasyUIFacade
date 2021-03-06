<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\DiffText;

class EuiDiffText extends EuiAbstractElement
{

    public function buildHtml()
    {
        $output = <<<HTML
				<div id="{$this->getId()}_diffcontainer" class="difftext-container">
					<pre id="{$this->getId()}_difforig" class="difftext-original" style="display: none;">
{$this->cleanText($this->getWidget()->getLeftValue())}
					</pre>
					<pre id="{$this->getId()}_diffnew" class="difftext-new" style="display: none;">
{$this->cleanText($this->getWidget()->getRightValue())}
					</pre>
					<pre id="{$this->getId()}_diff" class="difftext-diff">
					</pre>
				</div>
HTML;
        return $output;
    }

    public function buildJs()
    {
        return '
				$("#' . $this->getId() . '_diffcontainer").prettyTextDiff({
					cleanup: true,
					originalContainer: "#' . $this->getId() . '_difforig",
					changedContainer: "#' . $this->getId() . '_diffnew",
					diffContainer: "#' . $this->getId() . '_diff"
				});
				';
    }

    protected function cleanText($string)
    {
        return htmlspecialchars($string);
    }

    public function buildHtmlHeadTags()
    {
        return array(
            '<script type="text/javascript" src="' . $this->getFacade()->buildUrlToSource('LIBS.JQUERY_PRETTY_TEXT_DIFF') . '"></script>',
            '<script type="text/javascript" src="' . $this->getFacade()->buildUrlToSource('LIBS.DIFF_MATCH_PATCH') . '"></script>'
        );
    }

    /**
     *
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::getWidget()
     * @return DiffText
     */
    public function getWidget()
    {
        return parent::getWidget();
    }
}
?>