<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\AbstractAjaxFacade\Elements\JExcelTrait;
use exface\Core\Interfaces\Actions\ActionInterface;

/**
 * 
 * @author Andrej Kabachnik
 *
 */
class EuiInputKeysValues extends EuiInputText
{
    use JExcelTrait;

    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInputText::init()
     */
    protected function init()
    {
        parent::init();
        $this->setElementType('div');
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInputText::buildHtml()
     */
    public function buildHtml()
    {
        return $this->buildHtmlLabelWrapper($this->buildHtmlJExcel());
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInputText::buildJs()
     */
    public function buildJs()
    {
        return <<<JS
        
    $('#{$this->getId()}').jexcel({
        data: {$this->buildJsJExcelData()},
        allowRenameColumn: false,
        allowInsertColumn: false,
        allowDeleteColumn: false,
        allowInsertRow: false,
        allowDeleteRow: false,
        wordWrap: true,
        {$this->buildJsJExcelColumns()}
        minSpareRows: 0
    });
    
    {$this->buildJsFixAutoColumnWidth()}
    {$this->buildJsFixContextMenuPosition()}
    
JS;
    }
    
    /**
     * 
     * @return string
     */
    protected function buildJsJExcelData() : string
    {
        $widget = $this->getWidget();
        $values = json_decode($widget->getValue(), true);
        $keys = array_keys($values) ?? [];
        foreach ($widget->getReferenceValues() as $refVals) {
            $refKeys = array_keys($refVals);
            $keys = $refKeys + $keys;
        }
        
        foreach ($keys as $key) {
            $row = [$key, $values[$key]];
            foreach ($widget->getReferenceValues() as $refVals) {
                $row[] = $refVals[$key];
            }
            $data[] = $row;
        }
        
        return json_encode($data);
    }
    
    protected function buildJsJExcelColumns() : string
    {
        $widget = $this->getWidget();
        $columns = [
            [
                'title' => $widget->getCaptionForKeys(),
                'type' => 'text',
                'readOnly' => true,
                'align' => 'left'
            ],
            [
                'title' => $widget->getCaptionForValues() ?? $widget->getAttribute()->getName(),
                'type' => 'text',
                'align' => 'left'
            ]
        ];
        foreach (array_keys($this->getWidget()->getReferenceValues()) as $title) {
            $columns[] = [
                'title' => $title,
                'type' => 'text',
                'readOnly' => true,
                'align' => 'left'
            ];
        }
        
        return "
        columns: " . json_encode($columns) . ",";
    }
    
    /**
    *
    * @return string[]
    */
    public function buildHtmlHeadTags() : array
    {
        $includes = array_merge(
            parent::buildHtmlHeadTags(),
            $this->buildHtmlHeadTagsForJExcel()
            );
        
        array_unshift($includes, '<script type="text/javascript">' . $this->buildJsFixJqueryImportUseStrict() . '</script>');
        
        return $includes;
    }
    
    public function buildJsValueGetter()
    {
        return <<<JS
(function(){
    var aData = $('#{$this->getId()}').jexcel('getData', false);
    var oResult = {};
    for (var i = 0; i < aData.length; i++) {
        oResult[aData[i][0]] = (aData[i][1] === '' ? null : aData[i][1]);
    }
    return JSON.stringify(oResult);
}())
JS;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiInput::buildJsDataGetter()
     */
    public function buildJsDataGetter(ActionInterface $action = null)
    {
        return parent::buildJsDataGetter($action);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsDataSetter()
     */
    public function buildJsDataSetter(string $jsData) : string
    {
        return parent::buildJsDataSetter($jsData);
    }
}