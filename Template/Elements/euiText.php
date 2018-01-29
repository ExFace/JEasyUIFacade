<?php
namespace exface\JEasyUiTemplate\Template\Elements;

use exface\Core\Widgets\Text;
use exface\Core\Templates\AbstractAjaxTemplate\Elements\JqueryAlignmentTrait;
use exface\Core\DataTypes\TextStylesDataType;

/**
 * @method Text getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class euiText extends euiDisplay
{
    use JqueryAlignmentTrait;
    
    protected function init()
    {
        parent::init();
        $this->setElementType($this->getCaption() ? 'span' : 'p');
        return;
    }
    
    public function generateHtml()
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
?>