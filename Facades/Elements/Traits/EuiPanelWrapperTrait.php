<?php
namespace exface\JEasyUIFacade\Facades\Elements\Traits;

trait EuiPanelWrapperTrait
{    
    /***
     * Build HTML for when the Widget has Presets
     *
     * @param string $content
     * @return string
     */
    protected function buildHtmlPanelWrapper(string $content, string $footer) : string
    {
        return <<<HTML
        
<div class="easyui-panel" title="" data-options="fit: true, footer:'#footer_{$this->getId()}'">
    {$content}
</div>
<div id="footer_{$this->getId()}" style="padding:5px;">
    {$footer}
</div>

HTML;
    }
}