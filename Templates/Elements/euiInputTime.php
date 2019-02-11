<?php
namespace exface\JEasyUiTemplate\Templates\Elements;

use exface\Core\Widgets\InputTime;
use exface\Core\DataTypes\NumberDataType;
use exface\Core\Factories\DataTypeFactory;

/**
 * @method InputTime getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class euiInputTime extends euiInput
{
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiInput::init()
     */
    protected function init()
    {
        parent::init();
        $this->setElementType('timespinner');
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUiTemplate\Templates\Elements\euiInput::buildJsDataOptions()
     */
    protected function buildJsDataOptions()
    {
        $widget = $this->getWidget();
        $output = parent::buildJsDataOptions();
        $output = ($output ? ',' : '') . $output;
             
        $output .= "showSeconds: " . ($widget->getShowSeconds() ? 'true' : 'false')
                . ", increment: " . ($widget->getStepMinutes() < 60 ? $widget->getStepMinutes() : $widget->getStepMinutes() / 60)
                . ", highlight: " . ($widget->getStepMinutes() < 60 ? 1 : 0)
                ;
        return trim($output, ',');
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildHtmlHeadTags()
     */
    /*public function buildHtmlHeadTags()
    {
        $formatter = $this->getDataTypeFormatter();
        return array_merge(parent::buildHtmlHeadTags(), $formatter->buildHtmlHeadIncludes(), $formatter->buildHtmlBodyIncludes());
    }*/
}