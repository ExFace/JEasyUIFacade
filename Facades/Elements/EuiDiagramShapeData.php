<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Factories\WidgetLinkFactory;

use exface\Core\Interfaces\Widgets\WidgetLinkCoordinateInterface;

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

    /**
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsValueGetter()
     */
    public function buildJsValueGetter(string|WidgetLinkCoordinateInterface|null $coord = null): string
    {
        $coord = WidgetLinkFactory::createCoordinateFromAnything($coord);
        $this->validateValueGetterCoordinate($coord);
        $js = $this->getFacade()->getElement($this->getWidget()->getDiagram())->getId() . "_selected.data('oid')";
        return $js;
    }

    public function buildJsRefresh()
    {
        return $this->getFacade()->getElement($this->getWidget()->getDiagram())->buildJsRefresh();
    }
}
?>