<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\WebConsoleFacade;
use exface\Core\Factories\FacadeFactory;
use exface\Core\Widgets\Parts\ConsoleCommandPreset;

/**
 * JEasyUI Element to Display Console Terminal in the browser
 * 
 * @author rml
 * @method \exface\Core\Widgets\Console getWidget()
 */
class EuiConsole extends EuiAbstractElement
{
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildHtmlHeadTags()
     */
    public function buildHtmlHeadTags(){
        $facade = $this->getFacade();
        $includes = [];
        
        $includes[] = '<script type="text/javascript" src="' . $facade->buildUrlToSource('LIBS.TERMINAL.TERMINAL_JS') . '"></script>';
        $includes[] = '<script type="text/javascript" src="' . $facade->buildUrlToSource('LIBS.TERMINAL.ASCII_TABLE_JS') . '"></script>';
        $includes[] = '<script type="text/javascript" src="' . $facade->buildUrlToSource('LIBS.TERMINAL.UNIX_FORMATTING_JS') . '"></script>';
        $includes[] = '<link rel="stylesheet" href="' . $facade->buildUrlToSource('LIBS.TERMINAL.TERMINAL_CSS') . '"/>';
        
        return $includes;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::buildHtml()
     */
    public function buildHtml(){
        if ($this->getWidget()->hasCommandPresets() === true) {
            return $this->buildHtmlPanelWrapper($this->buildHtmlTerminal());
        } else {
            return $this->buildHtmlTerminal();
        }
    }
    
    /***
     * Build HTML for when the Widget has Presets
     * 
     * @param string $terminal
     * @return string
     */
    protected function buildHtmlPanelWrapper(string $terminal) : string
    {
        return <<<HTML

<div class="easyui-panel" title="" data-options="fit: true, footer:'#footer_{$this->getId()}'">
    {$terminal}
    {$this->buildHtmlCommandPresetDialogs()}
</div>
<div id="footer_{$this->getId()}" style="padding:5px;">
    {$this->buildHtmlCommandPresetButtons()}
</div>



HTML;
    }
    
    /**
     * Build HTML for Preset Buttons
     * 
     * @return string
     */
    protected function buildHtmlCommandPresetButtons() : string
    {
        $html = '';
        foreach ($this->getWidget()->getCommandPresets() as $nr => $preset) {
            $hint = str_replace('"', '&quot;', $preset->getHint());
            $html .= <<<HTML

    <a href="#" class="easyui-linkbutton" title="{$hint}" onclick="javascript: {$this->buildJsFunctionPrefix()}clickPreset{$nr}();" data-options="plain: true">{$preset->getCaption()}</a>

HTML;
        }
        return $html;
    }
    
    /**
     * Build HTML for Preset Dialogs
     * 
     * @return string
     */
    protected function buildHtmlCommandPresetDialogs() : string
    {
        $html = '';
        
        foreach ($this->getWidget()->getCommandPresets() as $nr => $preset) {
            if ($preset->hasPlaceholders() === true){
                $commands = json_encode($preset->getcommands());
                $dialogWidth = $this->getWidthRelativeUnit() + 35;
                
                $inputs = '<textarea name="commands" style="display: none;">' . $commands . '</textarea>';
                foreach ($preset->getPlaceholders() as $placeholder){
                    $placeholder = trim($placeholder, "<>");
                    $inputs .=<<<HTML

        <div class="exf-control exf-input" style="width: 100%;">
            <label>{$placeholder}</label>
			<div class="exf-labeled-item">
                <input class="easyui-textbox" required="true" style="height: 100%; width: 100%;" name="{$placeholder}" />
            </div>
        </div>
        
HTML;
                }
                
                $html .= <<<HTML
    
    <div id="{$this->getPresetDialogId($nr)}" class="easyui-dialog" title="{$preset->getCaption()}" style="width:{$dialogWidth}px;" data-options="closed:true,modal:true,border:'thin',buttons:'#{$this->getPresetDialogId($nr)}_buttons'">
        {$inputs}
        <div id="{$this->getPresetDialogId($nr)}_buttons" style="text-align: right !important;">
    		<a href="#" class="easyui-linkbutton {$this->getId()}_dialog_ok" data-options="">{$this->translate("WIDGET.CONSOLE_BTN_OK")}</a>
            <a href="#" class="easyui-linkbutton {$this->getId()}_dialog_close" data-options="plain: true">{$this->translate("WIDGET.CONSOLE_BTN_CANCEL")}</a>
    	</div>
    </div>

HTML;
            }            
        }
        
        return $html;            
    }
    
    /**
     * Returns preset Dialog with the given Number
     * 
     * @param int $presetIdx
     * @return string
     */
    protected function getPresetDialogId(int $presetIdx) : string
    {
        return "{$this->getId()}_presetDialog{$presetIdx}";
    }    
    
    /**
     * Build HTML for Console Terminal
     *
     * @return string
     */
    protected function buildHtmlTerminal() : string
    {
        return <<<HTML
        
    <div id="{$this->getId()}" style="min-height: 100%; margin: 0;"></div>
    
HTML;
    }
    
    /**
     * Build JS for preset button function
     * 
     * @param int $presetId
     * @param ConsoleCommandPreset $preset
     * @return string
     */
    protected function buildJsPresetButtonFunction(int $presetId, ConsoleCommandPreset $preset) :string
    {
        $commands = json_encode($preset->getCommands());
        if ($preset->hasPlaceholders()) {
            $action = "$('#{$this->getPresetDialogId($presetId)}').dialog('open').dialog('center');";
        } else {
            $action = $this->buildJsRunCommands($commands, "$('#{$this->getId()}').terminal()");
        }
        return <<<JS
            
function {$this->buildJsFunctionPrefix()}clickPreset{$presetId}() {
    {$action}
}

JS;
     
    }
        
    /**
     * Build JS to execute commands
     * 
     * @param string $commandsJs
     * @param string $terminalJs
     * @param string $placeholdersJs
     * @return string
     */
    protected function buildJsRunCommands(string $commandsJs, string $terminalJs, string $placeholdersJs = null) : string
    {
        $placeholdersJs = $placeholdersJs !== null ? ', ' . $placeholdersJs : '';
        return "{$this->buildJsFunctionPrefix()}ExecuteCommandsJson({$commandsJs}, {$terminalJs} {$placeholdersJs});";
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiAbstractElement::buildJs()
     */
    public function buildJs()
    {
        $consoleFacade = FacadeFactory::createFromString(WebConsoleFacade::class, $this->getWorkbench());
        
        $startCommands = $this->getWidget()->getStartCommands();
        if (empty($startCommands) === FALSE)
        {
            $runStartCommands = $this->buildJsRunCommands(json_encode($startCommands), "myTerm{$this->getId()}");
        }
            
        if($this->getWidget()->isDisabled()===true){
            $pauseIfDisabled = "function(){ {$this->buildJsDisabler()} }";
        }
            
        foreach ($this->getWidget()->getCommandPresets() as $nr => $preset) {
            $presetActions .= $this->buildJsPresetButtonFunction($nr, $preset);
        }
            
        return <<<JS

/**
 * Function to perform ajax request to Server.
 * Echo responses while request still running to the console terminal.
 *
 * @return jqXHR
 */
function {$this->buildJsFunctionPrefix()}ExecuteCommand(command, terminal) {
    terminal.pause();
    return $.ajax( {
		type: 'POST',
		url: '{$consoleFacade->buildUrlToFacade()}',
		data: {
            page: '{$this->getWidget()->getPage()->getAliasWithNamespace()}',
            widget: '{$this->getWidget()->getId()}',
			cmd: command,
            cwd: $('#{$this->getId()}').data('cwd')
		},
		xhrFields: {
			onprogress: function(e){
                var XMLHttpRequest = e.target;
                if (XMLHttpRequest.status >= 200 && XMLHttpRequest.status < 300) {
					var response = XMLHttpRequest.response;
					terminal.echo(String(response));
                    terminal.resume();
                    terminal.pause()                    
                }
			}
		},
        headers: {
            'Cache-Control': 'no-cache'
        }
    }).done(function(data, textStatus, jqXHR){
        $('#{$this->getId()}').data('cwd', jqXHR.getResponseHeader('X-CWD'));
        terminal.set_prompt({$this->getStyledPrompt("$('#{$this->getId()}').data('cwd')")});
        terminal.resume();    
    }).fail(function(jqXHR, textStatus, errorThrown){
        console.log('Error: ', errorThrown);
        {$this->buildJsShowError('jqXHR.responseText', 'jqXHR.status + " " + jqXHR.statusText')}
        terminal.resume();
    }).always({$pauseIfDisabled});
}

//Function to send commands given in an array to server one after another
function {$this->buildJsFunctionPrefix()}ExecuteCommandsJson(commandsarray, terminal, placeholders){            
    setTimeout(function(){ 
        $('#{$this->getId()}').terminal().focus(); 
    }, 0);
    
    if (placeholders && ! $.isEmptyObject(placeholders)) {
        for (var i in commandsarray) {
            for (var ph in placeholders) {
                commandsarray[i] = commandsarray[i].replace(ph, placeholders[ph]);
            }
        }
    }
    
    commandsarray.reduce(function (promise, command) {
        return promise
            .then(function(){
                return terminal.echo(terminal.get_prompt() + command);
            })
            .then(function(){
                return {$this->buildJsFunctionPrefix()}ExecuteCommand(command, terminal).promise();
            })
            
    }, Promise.resolve());
};

{$presetActions}

// Initialize the terminal emulator
$(function(){
    $('#{$this->getId()}').data('cwd', "{$this->getWidget()->getWorkingDirectoryPath()}");
    var myTerm{$this->getId()} = $('#{$this->getId()}').terminal(function(command) {
    	{$this->buildJsFunctionPrefix()}ExecuteCommand(command, myTerm{$this->getId()})
    }, {
        greetings: '{$this->getCaption()}',
        scrollOnEcho: true,
        prompt: {$this->getStyledPrompt("'" . $this->getWidget()->getWorkingDirectoryPath(). "'")}
    });

    {$runStartCommands}
    
    // Add click handlers to preset dialogs
    $('.{$this->getId()}_dialog_ok').click(function(event){
        var btn = $(this);
        var placeholders = {};
        var window = btn.parents('.panel.window').first();
        var commands = JSON.parse(window.find('textarea[name=commands]').val());
        window.find('.textbox-value').each(function(){
            placeholders['<'+ this.name +'>'] = this.value;
        });
        {$this->buildJsRunCommands('commands', "$('#{$this->getId()}').terminal()", 'placeholders')};
        window.find('.easyui-dialog').dialog('close');
    });
    $('.{$this->getId()}_dialog_close').click(function(event){
        setTimeout(function(){ $('#{$this->getId()}').terminal().focus(); }, 0);
        var btn = $(this);
        var window = btn.parents('.panel.window').first();
        window.find('.easyui-dialog').dialog('close');
    });

});

JS;
            
    }
        
    /**
     * Styles the prompt string.
     * See https://terminal.jcubic.pl/api_reference.php for allowed syntax 
     *
     * @param string $prompt
     * @return string
     */
    protected function getStyledPrompt(string $prompt) :string
    {
        return "'[[;lime;]' + " . $prompt . " + '> ]'";
    }
        
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsDisabler()
     */
    public function buildJsDisabler() : string
    {
        return "$('#{$this->getId()}').terminal().pause();";
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsEnabler()
     */
    public function buildJsEnabler() : string
    {
        return "$('#{$this->getId()}').terminal().resume();";
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::translate()
     */
    public function translate($message_id, array $placeholders = array(), $number_for_plurification = null)
    {
        $message_id = trim($message_id);
        return $this->getWorkbench()->getApp('exface.Core')->getTranslator()->translate($message_id, $placeholders, $number_for_plurification);
    }
}