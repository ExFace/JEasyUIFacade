<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Widgets\InputTime;
use exface\Core\DataTypes\NumberDataType;
use exface\Core\Factories\DataTypeFactory;

/**
 * @method InputTime getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
class EuiInputTime extends EuiInput
{
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::init()
     */
    protected function init()
    {
        parent::init();
        $this->setElementType('timespinner');
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJsDataOptions()
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
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildHtmlHeadTags()
     */
    /*public function buildHtmlHeadTags()
    {
        $formatter = $this->getDateFormatter();
        return array_merge(parent::buildHtmlHeadTags(), $formatter->buildHtmlHeadIncludes(), $formatter->buildHtmlBodyIncludes());
    }*/
}