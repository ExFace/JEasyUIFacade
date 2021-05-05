<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Interfaces\DataSheets\DataSheetInterface;
use exface\Core\Factories\DataSheetFactory;
use exface\Core\Exceptions\Widgets\WidgetConfigurationError;
use exface\Core\Interfaces\Actions\ActionInterface;

/**
 * The InputSelect widget will be rendered into a combobox in jEasyUI.
 *
 * @method \exface\Core\Widgets\InputCombo getWidget()
 *        
 * @author Andrej Kabachnik
 *        
 */
class EuiInputTags extends EuiInputSelect
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::getElementType()
     */
    public function getElementType()
    {
        return 'combo';
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildHtml()
     */
    public function buildHtml()
    {
        $widget = $this->getWidget();
        $output = '	<input style="height: 100%; width: 100%;"
						name="' . $widget->getAttributeAlias() . '"  
						id="' . $this->getId() . '"  
						' . ($widget->isRequired() ? 'required="true" ' : '') . '
						' . ($widget->isDisabled() ? 'disabled="disabled" ' : '') . '>
					';
        
        $values = $widget->getValues();
        foreach ($this->getWidget()->getTagsAvailable() as $key => $tag) {
            $class = in_array($key, $values) ? '' : 'l-btn-plain';
            $tagsHtml .= <<<HTML

                    <div class="exf-tag l-btn l-btn-small {$class}" data-tag-value="{$key}"><span class="l-btn-text">{$tag}</span></div>
HTML;
        }
        $output .= <<<HTML

                <div id="{$this->getId()}_tags" class="exf-tag-container">
                    {$tagsHtml}
                </div>

HTML;
        return $this->buildHtmlLabelWrapper($output);
    }
    
    public function buildJs()
    {
        $widget = $this->getWidget();
        if ($this->getWidget()->getMultiSelect()) {
            $delimVal = $this->getWidget()->getMultiSelectValueDelimiter();
            $delimText = $this->getWidget()->getMultiSelectTextDelimiter();
            $vals = $this->getWidget()->getValues();
            $tagsAvailable = $widget->getTagsAvailable();
            $texts = [];
            foreach ($vals as $i => $val) {
                $texts[$i] = $tagsAvailable[$val];
            }
            $setValuesJs =  "$('#{$this->getId()}').{$this->getElementType()}('setValues', ['" . implode("'$delimVal'", $vals) . "']).{$this->getElementType()}('setText', '" . implode($delimText, $texts) . "');";
        } else {
            $setValuesJs = '';
        }
        
        
        return <<<JS
            $(function() {
                $('#{$this->getId()}').{$this->getElementType()}({
                    editable: false,
                    {$this->buildJsInitOptions()}
                });

                $setValuesJs

                $('#{$this->getId()}_tags').appendTo($('#{$this->getId()}').combo('panel'));
                $('#{$this->getId()}_tags .exf-tag').click(function(){
                    var sVal = $(this).data('tag-value').toString();
                    var sText = $(this).children('span').first().text();
                    var jqCombo = $('#{$this->getId()}');
                    var aValues = jqCombo.combo('getValues') || [];
                    var sCurrentText = jqCombo.combo('getText') || '';
                    var aTexts = sCurrentText !== '' ? sCurrentText.split(',') : [];
                    var iValIdx = aValues.indexOf(sVal);

                    if (aValues[0] === '') {
                        aValues.shift();
                    }

                    switch (true) {
                        case iValIdx === -1: 
                            aValues.push(sVal);
                            aTexts.push(sText);
                            $(this).removeClass('l-btn-plain');
                            break;
                        case iValIdx === 0 && aValues.length === 1:
                            aValues = [];
                            aTexts = [];
                            $(this).addClass('l-btn-plain');
                            break;
                        default:
                            aValues.splice(iValIdx, 1);
                            aTexts.splice(iValIdx, 1);
                            $(this).addClass('l-btn-plain');
                    }

                    jqCombo.combo('setValues', aValues).combo('setText', aTexts.join(','));
                });
                
			    // Initialize the live refs, enablers/disablers, etc.
                {$this->buildJsEventScripts()};
            });
            
JS;
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsOptionMultiple() : string
    {
        // Enable multiselect. Make sure the "none" element is never selected and the selection is cleared when the user clicks on it.
        return $this->getWidget()->getMultiSelect() ? ", multiple:true" : '';
    }
    
    protected function buildJsOptionValue() : string
    {
        return '';
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJsDataGetter()
     */
    public function buildJsDataGetter(ActionInterface $action = null)
    {
        // If the object of the action is the same as that of the widget, treat
        // it as a regular input.
        if ($action === null || $this->getMetaObject()->is($action->getMetaObject()) || $action->getInputMapper($this->getMetaObject()) !== null) {
            return parent::buildJsDataGetter($action);
        }
        
        $widget = $this->getWidget();
        // If it's another object, we need to decide, whether to place the data in a
        // subsheet.
        if ($action->getMetaObject()->is($widget->getOptionsObject())) {
            // FIXME not sure what to do if the action is based on the object of the table.
            // This should be really important in lookup dialogs, but for now we just fall
            // back to the generic input logic.
            return parent::buildJsDataGetter($action);
        } elseif ($relPath = $widget->findRelationPathFromObject($action->getMetaObject())) {
            $relAlias = $relPath->toString();
        }
        
        if ($relAlias === null || $relAlias === '') {
            throw new WidgetConfigurationError($widget, 'Cannot use data from widget "' . $widget->getId() . '" with action on object "' . $action->getMetaObject()->getAliasWithNamespace() . '": no relation can be found from widget object to action object', '7CYA39T');
        }
        
        if ($widget->getMultiSelect() === false) {
            $rows = "[{ {$widget->getDataColumnName()}: {$this->buildJsValueGetter()} }]";
        } else {
            $delim = str_replace("'", "\\'", $this->getWidget()->getMultiSelectTextDelimiter());
            $rows = <<<JS
                            function(){
                                var aVals = ({$this->buildJsValueGetter()}).split('{$delim}');
                                var aRows = [];
                                aVals.forEach(function(sVal) {
                                    if (sVal !== undefined && sVal !== null && sVal !== '') {
                                        aRows.push({
                                            {$widget->getDataColumnName()}: sVal
                                        });
                                    }
                                })
                                return aRows;
                            }()
                            
JS;
        }
        
        return <<<JS
        
            {
                oId: '{$action->getMetaObject()->getId()}',
                rows: [
                    {
                        '{$relAlias}': {
                            oId: '{$widget->getMetaObject()->getId()}',
                            rows: {$rows}
                        }
                    }
                ]
            }
            
JS;
    }
}