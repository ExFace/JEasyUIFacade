<?php
namespace exface\JEasyUiTemplate\Templates\JEasyUiTemplate\Elements;

class euiMessage extends euiText
{

    function buildHtml()
    {
        if ($this->getWidget()->getWidth()->toString()) {
            $width = $this->getWidth();
        } else {
            $width = 'calc(100% - 20px)';
        }
        $output = '
				<div class="messager-body" style="width:' . $width . '">
					<div class="messager-icon ' . $this->getCssMessageType() . '"></div>
					<div>' . $this->getWidget()->getText() . '</div>
				</div>';
        return $this->buildHtmlGridItemWrapper($output);
    }

    function getCssMessageType()
    {
        switch ($this->getWidget()->getType()) {
            case EXF_MESSAGE_TYPE_ERROR:
                $output = 'messager-error';
                break;
            case EXF_MESSAGE_TYPE_WARNING:
                $output = 'messager-warning';
                break;
            case EXF_MESSAGE_TYPE_INFO:
                $output = 'messager-info';
                break;
            case EXF_MESSAGE_TYPE_SUCCESS:
                $output = 'messager-success';
                break;
        }
        return $output;
    }
}
?>