<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Interfaces\Actions\ActionInterface;

class EuiDiagramShapeData extends EuiAbstractElement
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
        $js = $this->getFacade()->getElement($this->getWidget()->getDiagram())->getId() . "_selected.data('oid')";
        return $js;
    }

    public function buildJsRefresh()
    {
        return $this->getFacade()->getElement($this->getWidget()->getDiagram())->buildJsRefresh();
    }
}
?>