<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\Text;
use exface\Core\Facades\AbstractAjaxFacade\Elements\JqueryAlignmentTrait;
use exface\Core\DataTypes\TextStylesDataType;

/**
 * @method Text getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class EuiText extends EuiDisplay
{
    use JqueryAlignmentTrait;    
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiDisplay::getElementType()
     */
    public function getElementType() : ?string
    {
        return $this->getCaption() ? 'span' : 'p';
    }
    
    public function buildHtml()
    {
        $widget = $this->getWidget();
        
        $style = 'text-align: ' . $this->buildCssTextAlignValue($widget->getAlign(), EXF_ALIGN_LEFT);
        $text = nl2br($this->getWidget()->getText());
        
        if ($widget->getAttribute()) {
            switch ($widget->getStyle()) {
                case TextStylesDataType::BOLD:
                    $style .= "font-weight: bold;";
                    break;
                case TextStylesDataType::ITALIC:
                    $style .= "font-style: italic;";
                    break;
                case TextStylesDataType::UNDERLINE:
                    $style .= "text-decoration: underline;";
                    break;
                case TextStylesDataType::UNDERLINE:
                    $style .= "text-decoration: line-through;";
                    break;
            }
        }
        
        $output = <<<HTML

        <{$this->getElementType()} id="{$this->getId()}" class="exf-text {$this->buildCssElementClass()}" style="{$style}">{$text}</{$this->getElementType()}>

HTML;
        return $this->buildHtmlLabelWrapper($output);
    }
}