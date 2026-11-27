<?php
namespace exface\JEasyUIFacade\Facades\Elements;

use exface\Core\DataTypes\DateTimeDataType;
use exface\Core\DataTypes\TimestampDataType;
use exface\Core\Factories\WidgetLinkFactory;
use exface\Core\Interfaces\Widgets\WidgetLinkCoordinateInterface;
use exface\Core\Widgets\InputDateRange;

/**
 * Uses a standard EasyUI combo panel to select a single inclusive range.
 *
 * @method InputDateRange getWidget()
 */
class EuiInputDateRange extends EuiInput
{
    public function getElementType() : ?string
    {
        return 'combo';
    }

    public function buildHtmlHeadTags()
    {
        $facade = $this->getFacade();
        return array_merge(parent::buildHtmlHeadTags(), [
            '<script src="' . $facade->buildUrlToSource('LIBS.MOMENT.JS') . '"></script>',
            '<script src="' . $facade->buildUrlToVendorFile('exface/jeasyuifacade/Facades/js/InputDateRange.js') . '"></script>'
        ]);
    }

    public function buildJs()
    {
        $widget = $this->getWidget();
        $type = $widget->getAttribute()?->getDataType();
        $groupCaptions = [];
        foreach (['single_dates', 'date_ranges', 'weeks', 'months', 'quarters', 'years'] as $group) {
            $groupCaptions[$group] = $this->translate('WIDGET.INPUTDATERANGE.GROUP.' . strtoupper($group));
        }
        $presets = array_map(static fn($range) => [
            'range' => $range->getRange(),
            'caption' => $range->getCaption(),
            'hint' => $range->getHint(),
            'group' => $range->getGroup(),
            'groupCaption' => $groupCaptions[$range->getGroup()] ?? $range->getGroup(),
            'from' => $range->getRangeFrom(),
            'to' => $range->getRangeTo()
        ], $widget->getRanges());
        if ($widget->getAllowDefaultOptions()) {
            foreach (['week' => 'weeks', 'month' => 'months'] as $period => $group) {
                foreach (['current', 'previous'] as $position) {
                    $range = $period . '_' . $position;
                    $presets[] = [
                        'range' => $range,
                        'caption' => $this->translate('WIDGET.INPUTDATERANGE.PRESET.' . strtoupper($range)),
                        'group' => $group,
                        'groupCaption' => $groupCaptions[$group],
                        'isDefault' => true
                    ];
                }
            }
        }
        $settings = json_encode([
            'format' => $widget->getFormat(),
            'firstDay' => $widget->getFirstDayOfWeek(),
            'dateTime' => $type instanceof DateTimeDataType || $type instanceof TimestampDataType,
            'allowCustomRange' => $widget->getAllowCustomRange(),
            'readonly' => $widget->isReadonly() || $widget->isDisplayOnly(),
            'presets' => $presets,
            'customText' => $this->translate('WIDGET.INPUTDATERANGE.CUSTOM'),
            'backText' => $this->translate('WIDGET.INPUTDATERANGE.BACK_TO_PRESETS'),
            'startText' => $this->translate('WIDGET.INPUTDATERANGE.SELECT_START'),
            'startDateText' => $this->translate('WIDGET.INPUTDATERANGE.START_DATE'),
            'endText' => $this->translate('WIDGET.INPUTDATERANGE.SELECT_END'),
            'clearText' => $this->translate('WIDGET.INPUTDATERANGE.CLEAR'),
            'invalidText' => $this->translate('MESSAGE.INVALID.INPUTDATE'),
            'validationRule' => $this->isValidationRequired() ? $this->getValidationRuleName() : null
        ], JSON_THROW_ON_ERROR | JSON_HEX_TAG);

        return <<<JS
exfInitDateRange($("#{$this->getId()}"), {{$this->buildJsDataOptions()}}, {$settings});
{$this->buildJsEventScripts()}
JS;
    }

    protected function buildsJsAddValidationType() : string
    {
        // The range control validates its scalar rather than its localized display text.
        return '';
    }

    public function buildJsValueGetterMethod()
    {
        return 'combo("getValue")';
    }

    public function buildJsValueGetter(string|WidgetLinkCoordinateInterface|null $coord = null): string
    {
        $this->validateValueGetterCoordinate(WidgetLinkFactory::createCoordinateFromAnything($coord));
        return <<<JS
(function(input) {
    return (input.data("combo") ? input.{$this->buildJsValueGetterMethod()} : input.val()) ?? "";
})($("#{$this->getId()}"))
JS;
    }

    public function buildJsValueSetterMethod($value)
    {
        return <<<JS
each((value => function() {
    var input = $(this);
    var setter = input.data('exfDateRangeSetValue');
    if (setter) {
        setter(value);
    } else {
        input.val(value);
    }
})({$value}))
JS;
    }

    public function buildJsValidator(?string $valJs = null) : string
    {
        if ($valJs === null && $this->isValidationRequired()) {
            return "(!$('#{$this->getId()}').data('combo') || $('#{$this->getId()}').combo('isValid'))";
        }
        return parent::buildJsValidator($valJs);
    }
}
