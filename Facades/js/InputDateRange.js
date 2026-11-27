/* global $, exfTools, moment */
/**
 * Builds a range picker from an EasyUI combo and calendar. The combo value
 * is the canonical `from..to` string; its visible text uses the widget's date format.
 */
function exfInitDateRange(input, comboOptions, settings) {
    const displaySeparator = " - ";
    const formatDate = date => exfTools.date.format(date, settings.format);
    const resolvePreset = preset => exfTools.date.resolveRangePreset(preset, settings.format, settings.firstDay === 1);

    const asDayRange = dates => exfTools.date.serializeRange(dates, settings.dateTime);

    function parseRange(value) {
        const range = exfTools.date.parseRange(value, settings.format, {dateTime: settings.dateTime});
        if (!range) {
            return null;
        }
        const {dates, hasTime} = range;
        const scalar = exfTools.date.serializeRange(dates, settings.dateTime, hasTime);
        const showTime = hasTime && scalar !== asDayRange(dates);
        const text = dates.map(date => formatDate(date) + (showTime ? " " + moment(date).format("HH:mm:ss") : "")).join(displaySeparator);
        return {scalar, text, dates};
    }

    const initialValue = String(input.val() ?? "");
    const initialRange = parseRange(initialValue);
    if (settings.validationRule) {
        // Validate the stored range, not the localized text shown by EasyUI.
        $.fn.validatebox.defaults.rules[settings.validationRule] = {
            validator: () => {
                const value = input.combo("getValue") ?? "";
                return !value || !!parseRange(value);
            },
            message: settings.invalidText
        };
    }

    const hasPresets = settings.presets.length > 0;
    let start = null;
    let calendar;
    let prompt;
    let selectedStart;
    let calendarView;
    let customButton;
    let backButton;
    const selectableDay = ".calendar-day:not(.calendar-disabled)";
    const onChange = comboOptions.onChange;
    input.combo({
        ...comboOptions,
        editable: false,
        readonly: settings.readonly,
        panelMinWidth: 240,
        panelHeight: "auto",
        value: initialRange?.scalar ?? initialValue,
        onChange(value, previous) {
            // An invalid value supplied by a setter should not trigger filter application.
            if (!value || parseRange(value)) {
                onChange?.call(this, value, previous);
            }
        },
        onShowPanel() {
            showCalendar(!hasPresets);
        }
    });
    input.combo("setText", initialRange?.text ?? initialValue);
    const textbox = input.combo("textbox");
    const popup = input.combo("panel");

    function setValue(value) {
        value = String(value ?? "");
        const range = parseRange(value);
        input.combo("setValue", range?.scalar ?? value);
        input.combo("setText", range?.text ?? value);
    }
    // The facade uses this setter for prefill, reset and saved-filter restoration.
    input.data("exfDateRangeSetValue", value => {
        const restoreFocus = popup[0].contains(document.activeElement);
        start = null;
        input.combo("hidePanel");
        if (restoreFocus) {
            textbox.trigger("focus");
        }
        setValue(value);
    });

    function commit(value) {
        input.combo("hidePanel");
        setValue(value);
        textbox.trigger("focus");
    }

    // Let EasyUI size and position the popup around a capped list and an always-visible footer.
    const panel = $("<div>").css({
        padding: "6px",
        boxSizing: "border-box",
        display: "flex",
        flexDirection: "column",
        maxHeight: "min(420px, 45vh)"
    }).appendTo(popup);
    const scrollable = {minHeight: 0, overflowY: "auto"};
    const presetsView = $("<div>").css(scrollable).appendTo(panel);
    function popupTabStops() {
        return popup.find('a[href], [tabindex="0"]').filter(":visible")
            .not('[tabindex="-1"], .l-btn-disabled');
    }

    textbox.on("keydown.exfDateRange", event => {
        const options = input.combo("options");
        if (options.disabled || options.readonly || !["Enter", " ", "ArrowDown"].includes(event.key)) {
            return;
        }
        event.preventDefault();
        event.stopImmediatePropagation();
        input.combo("showPanel");
        popupTabStops().first().trigger("focus");
    });
    popup.on("keydown.exfDateRange", event => {
        if (event.key === "Escape") {
            event.preventDefault();
            event.stopPropagation();
            input.combo("hidePanel");
            textbox.trigger("focus");
        } else if (event.key === "Tab") {
            const stops = popupTabStops();
            const boundary = event.shiftKey ? stops.first() : stops.last();
            if (event.target === boundary[0]) {
                input.combo("hidePanel");
                // Let the browser continue tabbing from the textbox, not the detached popup.
                textbox.trigger("focus");
            }
        } else if (event.key === " " && $(event.target).is("a.l-btn")) {
            event.preventDefault();
            event.target.click();
        }
    });

    function showCalendar(show) {
        presetsView.scrollTop(0);
        if (!calendarView) {
            return;
        }
        presetsView.toggle(!show);
        calendarView.css("display", show ? "flex" : "none");
        customButton?.toggle(!show);
        backButton?.toggle(show);
        if (show) {
            // Discard any unfinished selection and start near the last committed range.
            start = null;
            prompt.text(settings.startText);
            selectedStart.empty();
            const current = parseRange(input.combo("getValue") ?? "");
            calendar.calendar("moveTo", current?.dates[0] ?? new Date());
        }
        calendar.parent().scrollTop(0);
    }

    function setCalendarTabStop(picker, date) {
        const days = picker.find(selectableDay);
        const key = date && `${date.getFullYear()},${date.getMonth() + 1},${date.getDate()}`;
        let day = key ? days.filter(`[abbr="${key}"]`) : days.filter(".calendar-selected");
        if (!day.length) {
            day = days.not(".calendar-other-month").first();
        }
        days.attr("tabindex", -1);
        return day.attr("tabindex", 0);
    }

    function addButton(container, label, action, plain) {
        // EasyUI expects HTML here; supplying the escaped label during initialization avoids icon-only sizing.
        const button = $('<a href="#">').appendTo(container).linkbutton({
            plain,
            text: $("<span>").text(label).html()
        });
        button.on("click", event => {
            event.preventDefault();
            action();
        });
        return button;
    }
    const groups = new Map();
    settings.presets.forEach(preset => {
        const groupKey = `${preset.isDefault ? "default" : "configured"}:${preset.group ?? ""}`;
        if (!groups.has(groupKey)) {
            const group = $("<div>").appendTo(presetsView);
            if (preset.group) {
                $("<div>").addClass("combobox-group").attr({role: "heading", "aria-level": 3})
                    .text(preset.groupCaption).appendTo(group);
            }
            groups.set(groupKey, group);
        }
        // Resolve once for the fallback caption and again on selection so relative dates stay current.
        const dates = resolvePreset(preset);
        const caption = preset.caption || (dates ? dates.map(formatDate).join(displaySeparator) : preset.range);
        const button = addButton($("<div>").appendTo(groups.get(groupKey)), caption, () => {
            const selected = resolvePreset(preset);
            if (selected) {
                commit(asDayRange(selected));
            }
        }, true).css({display: "block", textAlign: "left"});
        if (!dates) {
            button.linkbutton("disable").attr({title: settings.invalidText, tabindex: -1});
        } else if (preset.hint) {
            button.attr("title", preset.hint);
        }
    });

    if (settings.allowCustomRange) {
        calendarView = $("<div>").css({minHeight: 0, flexDirection: "column"}).appendTo(panel);
        prompt = $("<div>").css("textAlign", "center").attr("aria-live", "polite")
            .css("flexShrink", 0)
            .text(settings.startText).appendTo(calendarView);
        const calendarScroll = $("<div>").css({...scrollable, scrollbarGutter: "stable both-edges"}).appendTo(calendarView);
        calendar = $("<div>").css({marginInline: "auto", maxWidth: "224px"}).appendTo(calendarScroll).calendar({
            width: "auto",
            height: 200,
            border: false,
            firstDay: settings.firstDay,
            onNavigate() {
                setCalendarTabStop($(this));
                $(this).find(selectableDay).each(function () {
                    const [year, month, day] = this.getAttribute("abbr").split(",").map(Number);
                    $(this).attr("aria-label", formatDate(new Date(year, month - 1, day)));
                });
            },
            onSelect(date) {
                // The first click is a draft; only the second commits a filter value.
                if (!start) {
                    start = date;
                    prompt.text(settings.endText);
                    selectedStart.text(`${settings.startDateText}: ${formatDate(date)}`);
                } else {
                    commit(asDayRange(exfTools.date.orderRangeDates([start, date])));
                }
            }
        });
        calendar.on("focusin.exfDateRange", selectableDay, function () {
            calendar.find(selectableDay).attr("tabindex", -1);
            $(this).attr("tabindex", 0);
        }).on("keydown.exfDateRange", selectableDay, function (event) {
            const offsets = {ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7};
            const offset = offsets[event.key];
            if (event.key === "Enter" || event.key === " ") {
                event.preventDefault();
                event.stopPropagation();
                this.click();
                if (start && calendar.is(":visible") && !this.isConnected) {
                    calendar.find(`${selectableDay}[tabindex="0"]`).trigger("focus");
                }
            } else if (offset !== undefined) {
                event.preventDefault();
                const [year, month, day] = this.getAttribute("abbr").split(",").map(Number);
                const next = new Date(year, month - 1, day + offset);
                const options = calendar.calendar("options");
                if (next.getFullYear() !== options.year || next.getMonth() + 1 !== options.month) {
                    // Change the visible month without selecting the focused date.
                    calendar.calendar({year: next.getFullYear(), month: next.getMonth() + 1});
                }
                setCalendarTabStop(calendar, next).trigger("focus");
            }
        });
        selectedStart = $("<div>").css({textAlign: "center", minHeight: "1.5em", flexShrink: 0})
            .attr("aria-live", "polite").appendTo(calendarView);
    }
    const footer = $("<div>").css({paddingTop: "6px", display: "flex", flexShrink: 0, gap: "6px"}).appendTo(panel);
    if (calendarView && hasPresets) {
        customButton = addButton(footer, settings.customText, () => {
            showCalendar(true);
            calendar.find(`${selectableDay}[tabindex="0"]`).trigger("focus");
        }, false);
        backButton = addButton(footer, settings.backText, () => {
            showCalendar(false);
            customButton.trigger("focus");
        }, false);
    }
    addButton(footer, settings.clearText, () => commit(""), false).css("margin-left", "auto");
    showCalendar(!hasPresets);
}
