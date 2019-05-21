<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\NavTiles;
use exface\Core\Widgets\Tile;
use exface\Core\Widgets\Tiles;

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
                $this->colorize($tiles);
            }
        } else {
            $tiles = $this->getWidget()->getWidgetFirst();
            $tiles->setHideCaption(true);
            $this->colorize($tiles);
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
    
    /**
     * Sets the color of each tile widget in the container if it does not have a color already
     * 
     * @param Tiles $tileContainer
     * @return Tiles
     */
    protected function colorize(Tiles $tileContainer) : Tiles
    {
        foreach ($tileContainer->getTiles() as $tile) {
            if (! $tile->getColor()) {
                $tile->setColor($this->getColor($tile));
            }
        }
        return $tileContainer;
    }
    
    /**
     * Returns the HTML color code for a tile, inheriting it from the upper menu level if possible.
     * 
     * @param Tile $tile
     * @return string
     */
    protected function getColor(Tile $tile) : string
    {
        if ($upperTile = $this->getWidget()->getUpperLevelTile($tile)) {
            return $this->getColor($upperTile);
        }
        
        $classes = $this->getColors();
        $idx = $tile->getParent()->getWidgetIndex($tile);
        return $classes[$idx % count($classes)];
    }
     
    /**
     * 
     * @return array
     */
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