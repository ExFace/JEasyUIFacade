<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\NavTiles;
use exface\Core\Widgets\Tile;

/**
 * 
 * @method NavTiles getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class EuiNavTiles extends EuiWidgetGrid 
{
    public function buildHtml()
    {
        if ($this->getWidget()->countWidgets() > 1) {
            foreach ($this->getWidget()->getWidgets() as $tiles) {
                $tiles->setNumberOfColumns(1);
                foreach ($tiles->getTiles() as $tile) {
                    if (! $tile->getColor()) {
                        $tile->setColor($this->getColor($tile));
                    }
                }
            }
        } else {
            $this->getWidget()->getWidgetFirst()->setHideCaption(true);
        }
        return parent::buildHtml();
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiWidgetGrid::getNumberOfColumnsByDefault()
     */
    public function getNumberOfColumnsByDefault() : int
    {
        return $this->getFacade()->getConfig()->getOption("WIDGET.NAVTILES.COLUMNS_BY_DEFAULT");
    }
    
    protected function getColor(Tile $tile) : string
    {
        if ($upperTile = $this->getWidget()->getUpperLevelTile($tile)) {
            return $this->getColor($upperTile);
        }
        
        $classes = $this->getColors();
        $idx = $tile->getParent()->getWidgetIndex($tile);
        return $classes[$idx % count($classes)];
    }
        
    protected function getColors() : array
    {
        return [
            '',
            '#f9f6e3',
            '#d7efed',
            '#e8e6f2',
            '#deedc4',
            '#e6defc',
            '#ffe9e8'
        ];
    }
}