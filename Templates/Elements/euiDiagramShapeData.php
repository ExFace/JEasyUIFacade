<?php
namespace exface\JEasyUiTemplate\Templates\Elements;

use exface\Core\Interfaces\Actions\ActionInterface;

class euiDiagramShapeData extends euiAbstractElement
{

    function buildHtml()
    {
        return '';
    }

    function buildJs()
    {
        return '';
    }

    public function buildJsDataGetter(ActionInterface $action = null)
    {
        if ($action) {
            $rows = "[{'" . $this->getMetaObject()->getUidAttributeAlias() . "': " . $this->buildJsValueGetter() . "}]";
        } else {
            // TODO
        }
        return "{oId: '" . $this->getWidget()->getMetaObject()->getId() . "', rows: " . $rows . "}";
    }

    public function buildJsValueGetter()
    {
        $js = $this->getTemplate()->getElement($this->getWidget()->getDiagram())->getId() . "_selected.data('oid')";
        return $js;
    }

    public function buildJsRefresh()
    {
        return $this->getTemplate()->getElement($this->getWidget()->getDiagram())->buildJsRefresh();
    }
}
?>