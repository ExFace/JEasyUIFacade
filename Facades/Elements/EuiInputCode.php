<?php
namespace exface\JEasyUIFacade\Facades\Elements;

/**
 * This InputCode facade uses the Ace editor alongside a set of formatters in order to display code.
 * 
 * @method \exface\Core\Widgets\InputCode getWidget()
 * 
 * @author Sergej Riel
 */
class EuiInputCode extends EuiInput
{
    protected function init()
    {
        parent::init();
    }

    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractAjaxFacade\Elements\AbstractJqueryElement::buildHtmlHeadTags()
     */
    public function buildHtmlHeadTags()
    {
        $widget = $this->getWidget();
        $codeLanguage = $widget->getLanguage();
        $colorizeCode = $widget->getCodeFormatter()->getColorize();
        
        $includes = parent::buildHtmlHeadTags();
        $facade = $this->getFacade();
        $includes[] = '<script type="text/javascript" src="' . $facade->buildUrlToSource('LIBS.INPUT_CODE.ACE.JS') . '"></script>';
        $includes[] = '<script type="text/javascript" src="' . $facade->buildUrlToSource('LIBS.INPUT_CODE.ACE.THEME') . '"></script>';
        $includes[] = '<script type="text/javascript" src="' . $facade->buildUrlToSource('LIBS.INPUT_CODE.SQL.FORMATTER') . '"></script>';
        
        if ($colorizeCode) {
            $includes[] = '<script type="text/javascript" src="' . $facade->buildUrlToSource('LIBS.INPUT_CODE.ACE.SRC') . '/mode-' . $codeLanguage .'.js' . '"></script>';
        }
        
        return $includes;
    }

    /**
     *
     * {@inheritDoc}
     * @see \exface\JEasyUIFacade\Facades\Elements\EuiValue::buildCssElementClass()
     */
    public function buildCssElementClass()
    {
        return parent::buildCssElementClass() . ' exf-inputcode';
    }
    
    public function buildHtml()
    {
        $widget = $this->getWidget();
        $editorWidth = $widget->getWidth()->getValue();
        $editorHeight = $widget->getHeight()->getValue();
        
        return <<<HTML
        <div id="exf-ace-editor" style="width: $editorWidth ; height: $editorHeight"></div>
HTML;
    }

    public function buildJs()
    {
        $js = parent::buildJs();
        
        $widget = $this->getWidget();
        $aceEditorLanguage = $widget->getLanguage();
        $inputValue = $widget->getValue();
        $editable = json_encode($widget->getEditable());
        $colorizeCode = json_encode($widget->getCodeFormatter()->getColorize());
        
        $js .= <<<JS
          
          const inputValue = `$inputValue`;
          
          const editor = ace.edit('exf-ace-editor', {
            theme: 'ace/theme/crimson_editor',
            readOnly: !$editable,
          });
          
          if ($colorizeCode) {
            editor.session.setMode('ace/mode/$aceEditorLanguage');
          }
          
          let formattedInput = {$this->buildJsFormatCode('inputValue')}

          editor.setValue(formattedInput, -1);
JS;
        
        return $js;
    }

    /**
     * Takes input string and gives formatted string back
     * It returns the input string if formatting fails or is disabled.
     * 
     * @param string $input
     * @return string
     */
    protected function buildJsFormatCode(string $input) : string
    {
        $widget = $this->getWidget();
        $codeFormatterLanguage = $widget->getCodeFormatter()->getLanguage();
        $codeFormatterDialect = $widget->getCodeFormatter()->getDialect();
        $prettifyCode = json_encode($widget->getCodeFormatter()->getPrettify());
        
        return <<<JS
      
      (function(input){
        if (!$prettifyCode) return input;
        
        const language = '{$codeFormatterLanguage}';
        const dialect = '{$codeFormatterDialect}';

        const FORMATTERS = {
          sql: (code, dialect) => {
            const language = dialect || 'sql';
            return sqlFormatter.format(code, {language});
          },
          json: (code) => {
              const obj = JSON.parse(code);
              return JSON.stringify(obj, null, 2);
          },

          //TODO SR: There is a formatter for many other languages: "prettier-standalone"
          // js: (code) => prettier.format(code, { parser: 'babel' }),
        };
        
          const formatter = FORMATTERS[language];
          
          if (!formatter) return input;
        
          try { 
            return formatter(input, dialect);
          } catch (e) {
            console.warn(
              "Code could not be formatted (language: " + language + ", dialect: " + dialect +")",
              e
            );
            return input;
          }
        
      })($input);
JS;

    }
}