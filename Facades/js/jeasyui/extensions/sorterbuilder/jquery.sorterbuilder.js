/**
 * Sorter list builder for jQuery EasyUI
 *
 * Renders an editor for a list of ExFace data sorters using regular jEasyUI controls
 * (combobox, linkbutton), so it looks exactly like the rest of the UI.
 *
 * The plugin works with the canonical ExFace sorter structure directly:
 *
 * [
 *     {"attribute_alias": "NAME", "direction": "ASC"},
 *     {"attribute_alias": "CREATED_ON", "direction": "DESC"}
 * ]
 *
 * The order of the sorters matters, so every row can be moved up and down.
 *
 * Usage:
 *
 * $('#sorters').exfSorterBuilder({
 *     attributes: [{attribute_alias: 'NAME', caption: 'Name'}],
 *     sorters: [{attribute_alias: 'NAME', direction: 'ASC'}]
 * });
 * var aSorters = $('#sorters').exfSorterBuilder('getSorters');
 *
 * @author Andrej Kabachnik
 */
(function ($) {

	var STYLE_ID = 'exf-sorterbuilder-style';
	var DATA_KEY = 'exfSorterBuilder';

	$(function () {
		if ($('#' + STYLE_ID).length === 0) {
			$('head').append(
				'<style id="' + STYLE_ID + '">'
				+ '.exf-sb {padding: 4px 2px;}'
				+ '.exf-sb-row {white-space: nowrap; padding: 2px 0;}'
				+ '.exf-sb-row > * {vertical-align: middle; margin-right: 4px;}'
				+ '.exf-sb-sorter {border: 1px dashed #ccc; border-radius: 3px; padding: 4px 6px; margin: 4px 0;}'
				+ '.exf-sb-empty {color: #999; padding: 4px 2px;}'
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
			state.options.onChange.call(target, state.sorters);
		}
	}

	function hasAttribute(opts, sAlias) {
		for (var i = 0; i < opts.attributes.length; i++) {
			if (opts.attributes[i].attribute_alias === sAlias) {
				return true;
			}
		}
		return false;
	}

	function newSorter(opts) {
		return {
			attribute_alias: (opts.attributes[0] || {}).attribute_alias || '',
			direction: opts.directions[0].value
		};
	}

	function normalizeSorters(opts, aSorters) {
		var aNormalized = [];
		(aSorters || []).forEach(function (oSorter) {
			aNormalized.push({
				attribute_alias: oSorter.attribute_alias || '',
				direction: String(oSorter.direction || opts.directions[0].value).toUpperCase()
			});
		});
		return aNormalized;
	}

	/**
	 * Destroys all easyui controls inside the given container - required because comboboxes
	 * keep their drop-down panels in the body and would leak on a re-render.
	 */
	function destroyControls(jqContainer) {
		jqContainer.find('.exf-sb-combo').each(function () {
			if ($(this).data('combobox')) {
				$(this).combobox('destroy');
			}
		});
	}

	function render(target) {
		var state = getState(target);
		var opts = state.options;
		var jqTarget = $(target);

		destroyControls(jqTarget);
		jqTarget.empty().addClass('exf-sb');

		if (state.sorters.length === 0) {
			$('<div class="exf-sb-empty"></div>').text(opts.i18n.empty).appendTo(jqTarget);
		}

		state.sorters.forEach(function (oSorter, iIdx) {
			renderSorter(target, oSorter, iIdx, jqTarget);
		});

		$('<div class="exf-sb-row exf-sb-toolbar"></div>').appendTo(jqTarget)
		.append(
			$('<a href="javascript:void(0)"></a>').linkbutton({
				plain: true,
				iconCls: opts.iconAdd,
				text: opts.i18n.add,
				onClick: function () {
					state.sorters.push(newSorter(opts));
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

	function renderSorter(target, oSorter, iIdx, jqParent) {
		var state = getState(target);
		var opts = state.options;
		var jqRow = $('<div class="exf-sb-row exf-sb-sorter"></div>').appendTo(jqParent);
		var fnMove = function (iOffset) {
			var iTarget = iIdx + iOffset;
			if (iTarget < 0 || iTarget >= state.sorters.length) {
				return;
			}
			state.sorters.splice(iTarget, 0, state.sorters.splice(iIdx, 1)[0]);
			rerender(target);
		};

		$('<input class="exf-sb-combo">').appendTo(jqRow).combobox({
			data: opts.attributes,
			valueField: 'attribute_alias',
			textField: 'caption',
			value: oSorter.attribute_alias,
			editable: true,
			panelHeight: opts.attributePanelHeight,
			width: opts.attributeWidth,
			onChange: function (sValue) {
				oSorter.attribute_alias = sValue;
				fireChange(target);
			}
		});

		$('<input class="exf-sb-combo">').appendTo(jqRow).combobox({
			data: opts.directions,
			valueField: 'value',
			textField: 'text',
			value: oSorter.direction,
			editable: false,
			panelHeight: 'auto',
			width: opts.directionWidth,
			onChange: function (sValue) {
				oSorter.direction = sValue;
				fireChange(target);
			}
		});

		$('<a href="javascript:void(0)" title="' + opts.i18n.moveUp + '"></a>').appendTo(jqRow).linkbutton({
			plain: true,
			iconCls: opts.iconUp,
			disabled: iIdx === 0,
			onClick: function () {
				fnMove(-1);
			}
		});

		$('<a href="javascript:void(0)" title="' + opts.i18n.moveDown + '"></a>').appendTo(jqRow).linkbutton({
			plain: true,
			iconCls: opts.iconDown,
			disabled: iIdx === state.sorters.length - 1,
			onClick: function () {
				fnMove(1);
			}
		});

		$('<a href="javascript:void(0)" title="' + opts.i18n.remove + '"></a>').appendTo(jqRow).linkbutton({
			plain: true,
			iconCls: opts.iconRemove,
			onClick: function () {
				state.sorters.splice(iIdx, 1);
				rerender(target);
			}
		});
	}

	$.fn.exfSorterBuilder = function (options, param) {
		if (typeof options === 'string') {
			return $.fn.exfSorterBuilder.methods[options](this, param);
		}
		options = options || {};
		return this.each(function () {
			var state = getState(this);
			if (state) {
				$.extend(state.options, options);
				if (options.i18n !== undefined) {
					state.options.i18n = $.extend({}, $.fn.exfSorterBuilder.defaults.i18n, options.i18n);
				}
				if (options.sorters !== undefined) {
					state.sorters = normalizeSorters(state.options, options.sorters);
				}
			} else {
				// Shallow extend only - a deep extend would merge the default arrays (directions)
				// into the passed ones item by item
				var opts = $.extend({}, $.fn.exfSorterBuilder.defaults, options);
				opts.i18n = $.extend({}, $.fn.exfSorterBuilder.defaults.i18n, options.i18n || {});
				state = $.data(this, DATA_KEY, {options: opts});
				state.sorters = normalizeSorters(opts, opts.sorters);
			}
			render(this);
		});
	};

	$.fn.exfSorterBuilder.methods = {
		options: function (jq) {
			return getState(jq[0]).options;
		},

		/**
		 * Returns the sorters currently configured - incomplete rows are skipped
		 */
		getSorters: function (jq) {
			var state = getState(jq[0]);
			var aSorters = [];
			state.sorters.forEach(function (oSorter) {
				if (oSorter.attribute_alias && hasAttribute(state.options, oSorter.attribute_alias)) {
					aSorters.push({
						attribute_alias: oSorter.attribute_alias,
						direction: oSorter.direction
					});
				}
			});
			return aSorters;
		},

		isEmpty: function (jq) {
			return $.fn.exfSorterBuilder.methods.getSorters(jq).length === 0;
		},

		setSorters: function (jq, aSorters) {
			return jq.each(function () {
				var state = getState(this);
				state.sorters = normalizeSorters(state.options, aSorters);
				render(this);
			});
		},

		clear: function (jq) {
			return jq.each(function () {
				getState(this).sorters = [];
				render(this);
				fireChange(this);
			});
		},

		destroy: function (jq) {
			return jq.each(function () {
				destroyControls($(this));
				$(this).empty().removeClass('exf-sb').removeData(DATA_KEY);
			});
		}
	};

	$.fn.exfSorterBuilder.defaults = {
		/**
		 * Array of `{attribute_alias, caption}` - the attributes available for sorting
		 */
		attributes: [],
		/**
		 * Array of `{attribute_alias, direction}` to start with
		 */
		sorters: [],
		directions: [
			{value: 'ASC', text: 'Ascending'},
			{value: 'DESC', text: 'Descending'}
		],
		attributeWidth: 260,
		attributePanelHeight: 250,
		directionWidth: 140,
		iconAdd: 'fa fa-plus',
		iconRemove: 'fa fa-times',
		iconUp: 'fa fa-arrow-up',
		iconDown: 'fa fa-arrow-down',
		i18n: {
			add: 'Sorter',
			remove: 'Remove sorter',
			moveUp: 'Move up',
			moveDown: 'Move down',
			empty: 'No sorters'
		},
		onChange: null
	};

})(jQuery);
