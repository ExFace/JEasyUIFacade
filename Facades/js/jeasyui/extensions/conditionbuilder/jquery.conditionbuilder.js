/**
 * Condition group builder for jQuery EasyUI
 *
 * Renders an editor for nested ExFace condition groups using regular jEasyUI controls
 * (combobox, textbox, linkbutton), so it looks exactly like the rest of the UI.
 *
 * The plugin works with the canonical ExFace condition group structure directly - there is no
 * translation between the controls and the data format:
 *
 * {
 *     "operator": "AND",
 *     "ignore_empty_values": true,
 *     "conditions": [
 *         {"expression": "NAME", "comparator": "=", "value": "test", "object_alias": "my.App.OBJECT", "apply_to_aggregates": false}
 *     ],
 *     "nested_groups": [
 *         {"operator": "OR", "ignore_empty_values": true, "conditions": [], "nested_groups": []}
 *     ]
 * }
 *
 * Values are normalized via `exfTools.data.filterComparator` and the optional data type parsers
 * passed in the `parsers` option - exactly like the header filters of a datagrid do it.
 *
 * Usage:
 *
 * $('#builder').exfConditionBuilder({
 *     objectAlias: 'my.App.OBJECT',
 *     fields: [{expression: 'NAME', caption: 'Name'}],
 *     parsers: {'NAME': function(mValue, sComparator){ return {comparator: sComparator, value: mValue}; }}
 * });
 * var oConditionGroup = $('#builder').exfConditionBuilder('getConditionGroup');
 *
 * @author Andrej Kabachnik
 */
(function ($) {

	var STYLE_ID = 'exf-conditionbuilder-style';
	var DATA_KEY = 'exfConditionBuilder';
	var COMPARATOR_BETWEEN = '..';

	$(function () {
		if ($('#' + STYLE_ID).length === 0) {
			$('head').append(
				'<style id="' + STYLE_ID + '">'
				+ '.exf-cb {padding: 4px 2px;}'
				+ '.exf-cb-group {border: 1px dashed #ccc; border-radius: 3px; padding: 4px 6px; margin: 4px 0;}'
				+ '.exf-cb-group-body {margin-left: 14px; padding-left: 10px; border-left: 1px dotted #ccc;}'
				+ '.exf-cb-row {white-space: nowrap; padding: 2px 0;}'
				+ '.exf-cb-row > * {vertical-align: middle; margin-right: 4px;}'
				+ '.exf-cb-empty {color: #999; padding: 4px 2px;}'
				+ '</style>'
			);
		}
	});

	function getState(target) {
		return $.data(target, DATA_KEY);
	}

	function fireChange(target) {
		var state = getState(target);
		if (state && typeof state.options.onChange === 'function') {
			state.options.onChange.call(target, state.group);
		}
	}

	function findField(opts, sExpression) {
		for (var i = 0; i < opts.fields.length; i++) {
			if (opts.fields[i].expression === sExpression) {
				return opts.fields[i];
			}
		}
		return null;
	}

	function getComparators(opts, sExpression) {
		var oField = findField(opts, sExpression);
		return (oField && oField.comparators && oField.comparators.length) ? oField.comparators : opts.comparators;
	}

	function hasComparator(aComparators, sComparator) {
		for (var i = 0; i < aComparators.length; i++) {
			if (aComparators[i].value === sComparator) {
				return true;
			}
		}
		return false;
	}

	function getComparatorHint(aComparators, sComparator) {
		for (var i = 0; i < aComparators.length; i++) {
			if (aComparators[i].value === sComparator) {
				return aComparators[i].hint || '';
			}
		}
		return '';
	}

	function escapeHtml(sText) {
		return String(sText === null || sText === undefined ? '' : sText)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function newCondition(opts) {
		var oField = opts.fields[0] || {};
		var aComparators = getComparators(opts, oField.expression);
		var sComparator = hasComparator(aComparators, opts.defaultComparator) ? opts.defaultComparator : (aComparators[0] || {}).value;
		return {
			expression: oField.expression || '',
			comparator: sComparator,
			value: ''
		};
	}

	function newGroup(opts, sOperator) {
		return {
			operator: sOperator || opts.operators[0].value,
			conditions: [],
			nested_groups: []
		};
	}

	/**
	 * Turns any (possibly incomplete) condition group into the internal model of the builder
	 */
	function normalizeGroup(opts, oGroup) {
		oGroup = oGroup || {};
		var oNormalized = {
			operator: oGroup.operator || opts.operators[0].value,
			conditions: [],
			nested_groups: []
		};
		(oGroup.conditions || []).forEach(function (oCondition) {
			oNormalized.conditions.push({
				expression: oCondition.expression || oCondition.attribute_alias || '',
				comparator: oCondition.comparator || opts.defaultComparator,
				value: (oCondition.value === null || oCondition.value === undefined) ? '' : oCondition.value
			});
		});
		(oGroup.nested_groups || []).forEach(function (oNested) {
			oNormalized.nested_groups.push(normalizeGroup(opts, oNested));
		});
		return oNormalized;
	}

	/**
	 * Destroys all easyui controls inside the given container - required because comboboxes
	 * keep their drop-down panels in the body and would leak on a re-render.
	 */
	function destroyControls(jqContainer) {
		jqContainer.find('.exf-cb-combo').each(function () {
			if ($(this).data('combobox')) {
				$(this).combobox('destroy');
			}
		});
		jqContainer.find('.exf-cb-text').each(function () {
			if ($(this).data('textbox')) {
				$(this).textbox('destroy');
			}
		});
	}

	function render(target) {
		var state = getState(target);
		var opts = state.options;
		var jqTarget = $(target);
		destroyControls(jqTarget);
		jqTarget.empty().addClass('exf-cb');
		renderGroup(target, state.group, null, jqTarget);

		// An additional button outside the top level group - just like the one of the sorters
		$('<div class="exf-cb-row exf-cb-footer"></div>').appendTo(jqTarget)
		.append(
			$('<a href="javascript:void(0)"></a>').linkbutton({
				plain: true,
				iconCls: opts.iconAddGroup,
				text: opts.i18n.addGroup,
				onClick: function () {
					state.group.nested_groups.push(newGroup(opts));
					rerender(target);
				}
			})
		);
	}

	/**
	 * Re-renders asynchronously - structural changes are triggered from handlers of controls,
	 * that are about to be destroyed by the re-render itself.
	 */
	function rerender(target) {
		setTimeout(function () {
			if (getState(target)) {
				render(target);
				fireChange(target);
			}
		}, 0);
	}

	function renderGroup(target, oGroup, oParentGroup, jqParent) {
		var opts = getState(target).options;
		var bRoot = (oParentGroup === null);
		var jqGroup = $('<div class="exf-cb-group"></div>').appendTo(jqParent);
		var jqHeader = $('<div class="exf-cb-row exf-cb-group-header"></div>').appendTo(jqGroup);
		var jqBody = $('<div class="exf-cb-group-body"></div>').appendTo(jqGroup);
		var fnAddGroup = function () {
			oGroup.nested_groups.push(newGroup(opts));
			rerender(target);
		};

		if (bRoot) {
			jqGroup.addClass('exf-cb-group-root');
		}

		$('<input class="exf-cb-combo">').appendTo(jqHeader).combobox({
			data: opts.operators,
			valueField: 'value',
			textField: 'text',
			value: oGroup.operator,
			editable: false,
			panelHeight: 'auto',
			width: opts.operatorWidth,
			onChange: function (sValue) {
				oGroup.operator = sValue;
				fireChange(target);
			}
		});

		$('<a href="javascript:void(0)"></a>').appendTo(jqHeader).linkbutton({
			plain: true,
			iconCls: opts.iconAddCondition,
			text: opts.i18n.addCondition,
			onClick: function () {
				oGroup.conditions.push(newCondition(opts));
				rerender(target);
			}
		});

		$('<a href="javascript:void(0)"></a>').appendTo(jqHeader).linkbutton({
			plain: true,
			iconCls: opts.iconAddGroup,
			text: opts.i18n.addGroup,
			onClick: fnAddGroup
		});

		if (! bRoot) {
			$('<a href="javascript:void(0)" title="' + opts.i18n.removeGroup + '"></a>').appendTo(jqHeader).linkbutton({
				plain: true,
				iconCls: opts.iconRemove,
				onClick: function () {
					var iIdx = $.inArray(oGroup, oParentGroup.nested_groups);
					if (iIdx > -1) {
						oParentGroup.nested_groups.splice(iIdx, 1);
					}
					rerender(target);
				}
			});
		}

		if (bRoot && oGroup.conditions.length === 0 && oGroup.nested_groups.length === 0) {
			$('<div class="exf-cb-empty"></div>').text(opts.i18n.empty).appendTo(jqBody);
		}

		oGroup.conditions.forEach(function (oCondition) {
			renderCondition(target, oCondition, oGroup, jqBody);
		});
		oGroup.nested_groups.forEach(function (oNested) {
			renderGroup(target, oNested, oGroup, jqBody);
		});
	}

	function renderCondition(target, oCondition, oGroup, jqParent) {
		var opts = getState(target).options;
		var jqRow = $('<div class="exf-cb-row exf-cb-condition"></div>').appendTo(jqParent);
		var jqField = $('<input class="exf-cb-combo">').appendTo(jqRow);
		var jqComparator = $('<input class="exf-cb-combo">').appendTo(jqRow);
		var jqValue = $('<input class="exf-cb-text">').appendTo(jqRow);
		var jqRemove = $('<a href="javascript:void(0)" title="' + opts.i18n.removeCondition + '"></a>').appendTo(jqRow);
		var fnUpdateValuePrompt = function () {
			var sPrompt = (oCondition.comparator === COMPARATOR_BETWEEN ? opts.i18n.valuePromptBetween : opts.i18n.valuePrompt);
			jqValue.textbox('options').prompt = sPrompt;
			jqValue.textbox('textbox').attr('placeholder', sPrompt);
		};
		var fnUpdateComparatorHint = function () {
			var sHint = getComparatorHint(getComparators(opts, oCondition.expression), oCondition.comparator);
			jqComparator.combobox('textbox').attr('title', sHint);
		};

		jqValue.textbox({
			value: oCondition.value,
			width: opts.valueWidth,
			onChange: function (sValue) {
				oCondition.value = sValue;
				fireChange(target);
			}
		});

		jqComparator.combobox({
			data: getComparators(opts, oCondition.expression),
			valueField: 'value',
			textField: 'text',
			value: oCondition.comparator,
			editable: false,
			panelHeight: 'auto',
			width: opts.comparatorWidth,
			formatter: function (oRow) {
				return '<span title="' + escapeHtml(oRow.hint || oRow.text) + '">' + escapeHtml(oRow.text) + '</span>';
			},
			onChange: function (sValue) {
				oCondition.comparator = sValue;
				fnUpdateValuePrompt();
				fnUpdateComparatorHint();
				fireChange(target);
			}
		});

		jqField.combobox({
			data: opts.fields,
			valueField: 'expression',
			textField: 'caption',
			value: oCondition.expression,
			editable: true,
			panelHeight: opts.fieldPanelHeight,
			width: opts.fieldWidth,
			onChange: function (sValue) {
				var aComparators = getComparators(opts, sValue);
				oCondition.expression = sValue;
				jqComparator.combobox('loadData', aComparators);
				if (! hasComparator(aComparators, oCondition.comparator)) {
					oCondition.comparator = (aComparators[0] || {}).value;
				}
				jqComparator.combobox('setValue', oCondition.comparator);
				fireChange(target);
			}
		});

		jqRemove.linkbutton({
			plain: true,
			iconCls: opts.iconRemove,
			onClick: function () {
				var iIdx = $.inArray(oCondition, oGroup.conditions);
				if (iIdx > -1) {
					oGroup.conditions.splice(iIdx, 1);
				}
				rerender(target);
			}
		});

		fnUpdateValuePrompt();
		fnUpdateComparatorHint();
	}

	/**
	 * Returns a ready-to-use ExFace condition group or NULL if there is nothing to filter over
	 */
	function toConditionGroup(target, oGroup) {
		var opts = getState(target).options;
		var oResult = {
			operator: oGroup.operator || opts.operators[0].value,
			ignore_empty_values: true,
			conditions: [],
			nested_groups: []
		};
		oGroup.conditions.forEach(function (oCondition) {
			var oParsed = toCondition(opts, oCondition);
			if (oParsed !== null) {
				oResult.conditions.push(oParsed);
			}
		});
		oGroup.nested_groups.forEach(function (oNested) {
			var oParsedGroup = toConditionGroup(target, oNested);
			if (oParsedGroup !== null) {
				oResult.nested_groups.push(oParsedGroup);
			}
		});
		if (oResult.conditions.length === 0 && oResult.nested_groups.length === 0) {
			return null;
		}
		return oResult;
	}

	function toCondition(opts, oCondition) {
		var sExpression = oCondition.expression;
		var sComparator = oCondition.comparator || opts.defaultComparator;
		var mValue = oCondition.value;
		var fnParser, oParsed, oInput, oValue;

		if (! sExpression || findField(opts, sExpression) === null) {
			return null;
		}
		if (mValue === null || mValue === undefined || String(mValue) === '') {
			return null;
		}

		fnParser = opts.parsers[sExpression];
		oParsed = (typeof fnParser === 'function') ? fnParser(mValue, sComparator) : {comparator: sComparator, value: mValue};
		// BETWEEN is entered as a single `from..to` string, so its bounds must be split before parsing
		oInput = (oParsed.comparator === COMPARATOR_BETWEEN)
			? exfTools.data.filterComparator.extract(String(oParsed.value))
			: {comparator: oParsed.comparator, value: oParsed.value};
		oValue = exfTools.data.filterComparator.parseValue(oInput);
		if (oValue.hasValue !== true) {
			return null;
		}

		return {
			expression: sExpression,
			comparator: oParsed.comparator,
			value: oValue.value,
			object_alias: opts.objectAlias,
			apply_to_aggregates: false
		};
	}

	$.fn.exfConditionBuilder = function (options, param) {
		if (typeof options === 'string') {
			return $.fn.exfConditionBuilder.methods[options](this, param);
		}
		options = options || {};
		return this.each(function () {
			var state = getState(this);
			if (state) {
				$.extend(state.options, options);
				if (options.i18n !== undefined) {
					state.options.i18n = $.extend({}, $.fn.exfConditionBuilder.defaults.i18n, options.i18n);
				}
				if (options.group !== undefined) {
					state.group = normalizeGroup(state.options, options.group);
				}
			} else {
				// Shallow extend only - a deep extend would merge the default arrays (comparators,
				// operators) into the passed ones item by item
				var opts = $.extend({}, $.fn.exfConditionBuilder.defaults, options);
				opts.i18n = $.extend({}, $.fn.exfConditionBuilder.defaults.i18n, options.i18n || {});
				state = $.data(this, DATA_KEY, {options: opts});
				state.group = normalizeGroup(opts, opts.group);
			}
			render(this);
		});
	};

	$.fn.exfConditionBuilder.methods = {
		options: function (jq) {
			return getState(jq[0]).options;
		},

		/**
		 * Returns the ExFace condition group currently configured or NULL if it is empty
		 */
		getConditionGroup: function (jq) {
			return toConditionGroup(jq[0], getState(jq[0]).group);
		},

		isEmpty: function (jq) {
			return toConditionGroup(jq[0], getState(jq[0]).group) === null;
		},

		/**
		 * Returns a copy of the internal model - including incomplete conditions
		 */
		getModel: function (jq) {
			return $.extend(true, {}, getState(jq[0]).group);
		},

		setModel: function (jq, oGroup) {
			return jq.each(function () {
				var state = getState(this);
				state.group = normalizeGroup(state.options, oGroup);
				render(this);
				fireChange(this);
			});
		},

		clear: function (jq) {
			return jq.each(function () {
				var state = getState(this);
				state.group = newGroup(state.options);
				render(this);
				fireChange(this);
			});
		},

		destroy: function (jq) {
			return jq.each(function () {
				destroyControls($(this));
				$(this).empty().removeClass('exf-cb').removeData(DATA_KEY);
			});
		}
	};

	$.fn.exfConditionBuilder.defaults = {
		/**
		 * Alias of the meta object with namespace - used as `object_alias` of every condition
		 */
		objectAlias: '',
		/**
		 * Array of `{expression, caption, comparators}` - `comparators` is optional and
		 * overrides the global comparator list for that field
		 */
		fields: [],
		/**
		 * Object with expressions for keys and `function(mValue, sComparator)` for values,
		 * each returning `{comparator, value}` - normally built from the data type of a field
		 */
		parsers: {},
		/**
		 * Initial condition group
		 */
		group: null,
		defaultComparator: '=',
		operators: [
			{value: 'AND', text: 'AND'},
			{value: 'OR', text: 'OR'}
		],
		/**
		 * Array of `{value, text, hint}` - `hint` is shown as tooltip
		 */
		comparators: [
			{value: '=', text: 'is'},
			{value: '!=', text: 'is not'},
			{value: '==', text: 'equals'},
			{value: '!==', text: 'does not equal'},
			{value: '<', text: 'less than'},
			{value: '<=', text: 'less than or equals'},
			{value: '>', text: 'greater than'},
			{value: '>=', text: 'greater than or equals'},
			{value: '[', text: 'in list'},
			{value: '![', text: 'not in list'},
			{value: '..', text: 'between'}
		],
		fieldWidth: 220,
		fieldPanelHeight: 250,
		comparatorWidth: 170,
		valueWidth: 220,
		operatorWidth: 80,
		iconAddCondition: 'fa fa-plus',
		iconAddGroup: 'fa fa-plus-square-o',
		iconRemove: 'fa fa-times',
		i18n: {
			addCondition: 'Condition',
			addGroup: 'Group',
			removeCondition: 'Remove condition',
			removeGroup: 'Remove group',
			empty: 'No conditions',
			valuePrompt: '',
			valuePromptBetween: 'from..to'
		},
		onChange: null
	};

})(jQuery);
