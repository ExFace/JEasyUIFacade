<?php
namespace exface\JEasyUiTemplate\Templates\Elements;

use exface\Core\Widgets\InputSelect;

/**
 * The InputSelect widget will be rendered into a combobox in jEasyUI.
 *
 * @method InputSelect getWidget()
 *        
 * @author Andrej Kabachnik
 *        
 */
class euiInputSelect extends euiInput
{

    protected function init()
    {
        parent::init();
        $this->setElementType('combobox');
    }

    public function buildHtml()
    {
        $widget = $this->getWidget();
        $options = '';
        $selected_cnt = count($this->getWidget()->getValues());
        foreach ($widget->getSelectableOptions() as $value => $text) {
            if ($this->getWidget()->getMultiSelect() && $selected_cnt > 1 && $value !== '' && ! is_null($value)) {
                $selected = in_array($value, $this->getWidget()->getValues());
            } else {
                $selected = strcasecmp($this->getWidget()->getValueWithDefaults(), $value) == 0 ? true : false;
            }
            $options .= '
					<option value="' . $value . '"' . ($selected ? ' selected="selected"' : '') . '>' . $text . '</option>';
        }
        
        $output = '	<select style="height: 100%; width: 100%;" class="easyui-' . $this->getElementType() . '" 
						name="' . $widget->getAttributeAlias() . '"  
						id="' . $this->getId() . '"  
						' . ($widget->isRequired() ? 'required="true" ' : '') . '
						' . ($widget->isDisabled() ? 'disabled="disabled" ' : '') . '
						' . ($this->buildJsDataOptions() ? 'data-options="' . $this->buildJsDataOptions() . '" ' : '') . '>
						' . $options . '
					</select>
					';
        return $this->buildHtmlLabelWrapper($output);
    }

    public function buildJs()
    {
        $output = '';
        return $output;
    }

    /**
     * Diese Funktion prueft zunaechst ob das JEasyUi-Element auch vorhanden ist.
     * Wenn
     * ja wird es aufgerufen um den momentanen Wert zurueckzugeben, wenn nein wird die
     * jquery-Funktion .val() verwendet um einen Wert zurueckzugeben. Wird der value-
     * Getter aufgerufen bevor das Element initialisiert ist entsteht sonst ein Fehler.
     *
     * {@inheritdoc}
     *
     * @see \exface\Core\Templates\AbstractAjaxTemplate\Elements\AbstractJqueryElement::buildJsValueGetter()
     */
    public function buildJsValueGetter()
    {
        if ($this->getWidget()->getMultiSelect()) {
            $value_getter = <<<JS
return $("#{$this->getId()}").{$this->getElementType()}("getValues").join();
JS;
        } else {
            $value_getter = <<<JS
return $("#{$this->getId()}").{$this->getElementType()}("getValue");
JS;
        }
        
        $output = <<<JS

(function(){
	var jqself = $('#{$this->getId()}');
	if (jqself.data("{$this->getElementType()}")) {
		{$value_getter}
	} else {
        var value = '';
        $.each(jqself.children('option[selected=selected]'), function(){
            value += (value !== '' ? '{$this->getWidget()->getMultiSelectValueDelimiter()}' : '') + this.value;
        });
        return value;
	}
})()
JS;
        return $output;
    }

    public function buildJsDataOptions()
    {
        $widget = $this->getWidget();
        return "panelHeight: 'auto'" 
            // Enable multiselect. Make sure the "none" element is never selected and the selection is cleared when the user clicks on it.
            . ($this->getWidget()->getMultiSelect() ? ", multiple:true, onShowPanel: function(){ $(this).{$this->getElementType()}('unselect',''); }, onSelect: function(record){ if(record.value == '') $(this).{$this->getElementType()}('clear'); }" : '')  
            // Increase hight automatically for multiline selects
            . ($widget->getHeight()->isRelative() && $widget->getHeight()->getValue() > 1 ? ", multiline:true" : '') 
            // Set the value programmatically
            . ($this->getWidget()->getMultiSelect() && count($this->getWidget()->getValues()) > 1 ? ", value:['" . implode("'" . $this->getWidget()->getMultiSelectValueDelimiter() . "'", $this->getWidget()->getValues()) . "']" : '')
            ;
    }
}
?>