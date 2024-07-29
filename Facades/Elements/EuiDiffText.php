<?php
namespace exface\JEasyUIFacade\Facades\Elements;

/**
 * 
 * @author andrej.kabachnik
 * 
 * @method \exface\Core\Widgets\DiffText getWidget()
 *
 */
class EuiDiffText extends EuiAbstractElement
{

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::buildHtml()
     */
    public function buildHtml()
    {
        $output = <<<HTML
				<div id="{$this->getId()}_diffcontainer" class="difftext-container">
					<pre id="{$this->getId()}_difforig" class="difftext-original" style="display: none;">
{$this->escapeString($this->getWidget()->getLeftValue(), false, true)}
					</pre>
					<pre id="{$this->getId()}_diffnew" class="difftext-new" style="display: none;">
{$this->escapeString($this->getWidget()->getRightValue(), false, true)}
					</pre>
					<pre id="{$this->getId()}_diff" class="difftext-diff">
					</pre>
				</div>
HTML;
        return $output;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::buildJs()
     */
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

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildHtmlHeadTags()
     */
    public function buildHtmlHeadTags()
    {
        return array(
            '<script type="text/javascript" src="' . $this->getFacade()->buildUrlToSource('LIBS.JQUERY_PRETTY_TEXT_DIFF') . '"></script>',
            '<script type="text/javascript" src="' . $this->getFacade()->buildUrlToSource('LIBS.DIFF_MATCH_PATCH') . '"></script>'
        );
    }
}