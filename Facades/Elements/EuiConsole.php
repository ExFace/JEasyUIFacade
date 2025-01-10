<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\Facades\WebConsoleFacade;
use exface\Core\Factories\FacadeFactory;
use exface\Core\Widgets\Console;
use exface\Core\Widgets\Parts\ConsoleCommandPreset;
use exface\JEasyUIFacade\Facades\Elements\Traits\EuiPanelWrapperTrait;

/**
 * JEasyUI Element to Display Console Terminal in the browser
 * 
 * @author rml
 * @method \exface\Core\Widgets\Console getWidget()
 */
class EuiConsole extends EuiAbstractElement
{
    use EuiPanelWrapperTrait;
    
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
            return $this->buildHtmlPanelWrapper($this->buildHtmlTerminal(), $this->buildHtmlCommandPresetButtons());
        } else {
            return $this->buildHtmlTerminal();
        }
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
            $hint = $this->buildHintText($preset->getHint() . " (" . implode("; ", $preset->getCommands()) . ")");
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
        
    <div id="{$this->getId()}" style="min-height: 100%; margin: 0;" class="{$this->buildCssElementClass()}"></div>
    
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
			<div class="exf-labeled-item" style="width: 60%;">
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
            // Add click handlers to preset dialogs
            var jqBtnOK = $(jqToolbar).find('.exf-console-preset-btn-ok').click(function(event){
                var placeholders = {};
                var commands = {$commands};
                jqDialog.find('.textbox-value').each(function(){
                    placeholders['<'+ this.name +'>'] = this.value;
                });
                {$this->buildJsRunCommands('commands', "$('#{$this->getId()}').terminal()", 'placeholders')};
                jqDialog.dialog('close');
            });
            $(jqToolbar).find('.exf-console-preset-btn-close').click(function(event){
                setTimeout(function(){ $('#{$this->getId()}').terminal().focus(); }, 0);
                jqDialog.dialog('close');
            });
            jqDialog.find('input').keyup(function (ev) {
                var keycode = (ev.keyCode ? ev.keyCode : ev.which);
                if (keycode == '13') {
                    jqBtnOK.click();
                }
            })
            jqDialog.find('.easyui-textbox').first().next().find('input').focus();
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
     * @param string $aCommandsJs
     * @param string $terminalJs
     * @param string $placeholdersJs
     * @return string
     */
    protected function buildJsRunCommands(string $aCommandsJs, string $terminalJs, string $placeholdersJs = null) : string
    {
        $placeholdersJs = $placeholdersJs !== null ? ', ' . $placeholdersJs : '';
        return "{$this->buildJsFunctionPrefix()}ExecuteCommandsJson({$aCommandsJs}, {$terminalJs} {$placeholdersJs});";
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
            $pauseIfDisabled = "function(){ {$this->buildJsSetDisabled(true)} }";
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
    var responseCumulated = '';
    return $.ajax( {
		type: 'POST',
		url: '{$consoleFacade->buildUrlToFacade(true)}',
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
					var response = String(XMLHttpRequest.response);
					if (responseCumulated.length < response.length) {
                        if (response.substring(0, responseCumulated.length) === responseCumulated) {
                            response = response.substring(responseCumulated.length);
                            responseCumulated += response;
                        }
                    }
                    // Replace trailing newline as .echo() will add one itself.
                    terminal.echo(response.replace(/\\n$/, ""));
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
        {$this->buildJsShowErrorAjax('jqXHR')}
        terminal.resume();
    }).always({$pauseIfDisabled});
}

//Function to send commands given in an array to server one after another
function {$this->buildJsFunctionPrefix()}ExecuteCommandsJson(commandsarray, terminal, placeholders){            
    setTimeout(function(){ 
        terminal.focus(); 
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
                terminal.history().append(command);
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
        greetings: {$this->escapeString($this->getCaption(), true, false)},
        execHistory: true,
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
     * {@inheritdoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildJsSetDisabled()
     */
    public function buildJsSetDisabled(bool $trueOrFalse) : string
    {
        if ($trueOrFalse === true) {
            return "$('#{$this->getId()}').terminal().pause();";
        } else {
            return "$('#{$this->getId()}').terminal().resume();";
        }
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
    
    public function buildCssElementClass()
    {
        return 'exf-console';
    }

    public function buildJsCallFunction(string $functionName = null, array $parameters = []) : string
    {
        switch (true) {
            case $functionName === Console::FUNCTION_RUN_COMMAND:
                $cmd = $parameters[0];
                $cmd = trim(trim($cmd), '"');
                return "{$this->buildJsFunctionPrefix()}ExecuteCommandsJson([{$this->escapeString($cmd, true, false)}], $('#{$this->getId()}').terminal());";
        }
        return parent::buildJsCallFunction($functionName, $parameters);
    }
}