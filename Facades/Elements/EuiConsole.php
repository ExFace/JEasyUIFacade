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
            $hint = str_replace('"', '&quot;', $preset->getHint() . " (" . implode("; ", $preset->getCommands()) . ")");
            $dataOptions = '';
            if ($preset->getVisibility() !== EXF_WIDGET_VISIBILITY_PROMOTED) {
                $dataOptions .= ', plain: true';
            }
            $dataOptions = trim($dataOptions, " ,");
            $html .= <<<HTML

    <a href="#" class="easyui-linkbutton" title="{$hint}" onclick="javascript: {$this->buildJsFunctionPrefix()}clickPreset{$nr}();" data-options="{$dataOptions}">{$preset->getCaption()}</a>

HTML;
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
     * The resulting JS function will
     * - call the server to perform all commands if neither of them has placeholders
     * - create an easyui-dialog for placeholders and perform the commands after 
     * the OK-button of the dialog is pressed. The dialog will be destroyed after
     * being closed.
     * 
     * Destroying the dialog after closing it is important as otherwise it's parts
     * will remain in the DOM causing broken future dialogs.
     * 
     * @param int $presetId
     * @param ConsoleCommandPreset $preset
     * @return string
     */
    protected function buildJsPresetButtonFunction(int $presetId, ConsoleCommandPreset $preset) :string
    {
        $commands = json_encode($preset->getCommands());
        if ($preset->hasPlaceholders()) {
            $commands = json_encode($preset->getcommands());
            $dialogWidth = $this->getWidthRelativeUnit() + 35;
            
            $addInputsJs = '';
            foreach ($preset->getPlaceholders() as $placeholder){
                $placeholder = trim($placeholder, "<>");
                $addInputsJs .= <<<js

    jqDialog.append(`
        <div class="exf-control exf-input" style="width: 100%;">
            <label>{$placeholder}</label>
			<div class="exf-labeled-item">
                <input class="easyui-textbox" required="true" style="height: 100%; width: 100%;" name="{$placeholder}" />
            </div>
        </div>
    `);
        
js;
            }
            
            $action = <<<JS

    var jqDialog = $(`
<div class="exf-console-preset-dialog" title="{$preset->getCaption()}" style="width:{$dialogWidth}px;">
    <div class="exf-console-preset-dialog-buttons" style="text-align: right !important;">
		<a href="#" class="easyui-linkbutton exf-console-preset-btn-ok" data-options="">{$this->translate("WIDGET.CONSOLE.PRESET_BTN_OK")}</a>
        <a href="#" class="easyui-linkbutton exf-console-preset-btn-close" data-options="plain: true">{$this->translate("WIDGET.CONSOLE.PRESET_BTN_CANCEL")}</a>
	</div>
</div>
`);
    {$addInputsJs}
    jqDialog.attr('id', '{$this->getPresetDialogId($presetId)}');
    jqDialog.find('.exf-console-preset-dialog-buttons').attr('id', '{$this->getPresetDialogId($presetId)}_buttons');
    $('body').append(jqDialog);
    $.parser.parse(jqDialog);
    jqDialog.dialog({
        closed:true,
        modal:true,
        border:'thin',
        buttons:'#{$this->getPresetDialogId($presetId)}_buttons',
        onOpen: function() {
            var jqToolbar = $('#{$this->getPresetDialogId($presetId)}_buttons');
            console.log('opened', $(jqToolbar).find('.console-preset-button-ok'));
            // Add click handlers to preset dialogs
            $(jqToolbar).find('.exf-console-preset-btn-ok').click(function(event){
                var placeholders = {};
                var commands = {$commands};
                jqDialog.find('.textbox-value').each(function(){
                    console.log('found ph', this.name);
                    placeholders['<'+ this.name +'>'] = this.value;
                });
                {$this->buildJsRunCommands('commands', "$('#{$this->getId()}').terminal()", 'placeholders')};
                jqDialog.dialog('close');
            });
            $(jqToolbar).find('.exf-console-preset-btn-close').click(function(event){
                setTimeout(function(){ $('#{$this->getId()}').terminal().focus(); }, 0);
                jqDialog.dialog('close');
            });
        },
        onClose: function() {
            jqDialog.dialog('destroy');
        }
    });
    jqDialog.dialog('open').dialog('center');

JS;
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
        return "'[[;aqua;]' + " . $prompt . " + '> ]'";
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