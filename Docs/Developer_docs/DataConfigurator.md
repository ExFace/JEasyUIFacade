# jEasyUI data configurator

The `EuiDataConfigurator` renders the personalization of a data widget in two places:

1. the **header panel** above the data widget - the quick filters and the toolbar with the buttons,
2. the **configurator dialog** - a tabbed jEasyUI dialog with sorting, advanced search and (if the
   header is hidden permanently) the filters.

Everything belonging to the configurator lives in
[`Facades/Elements/EuiDataConfigurator.php`](../../Facades/Elements/EuiDataConfigurator.php). The
data elements only call into it: `EuiData::buildHtmlTableHeader()` renders the header and adds the
buttons, `EuiDataTable::buildJs()` and `EuiDataElementTrait::buildJsForPanel()` emit the JS via
`buildJsForPanelHeader()`.

## The header

`buildHtmlHeaderPanel()` produces a collapsible easyui panel with the filters and a footer with the
toolbars of the data widget:

```
<div id="..._toolbar">
    <div class="easyui-panel exf-data-header">   <!-- quick filters -->
    <div id="..._toolbar_footer">                <!-- toolbars with buttons -->
</div>
<div id="..._dialog">                            <!-- configurator dialog, hidden -->
```

### Visibility of the header and the toolbar

| Model | Filters | Toolbar with buttons |
|---|---|---|
| default | visible | visible |
| `hide_header: true` | in the configurator dialog | visible |
| `hide_header: true` + `hide_caption: true` | in the configurator dialog | hidden |
| `hide_header_toolbar: false` | as above | always visible |
| `hide_header_toolbar: true` | as above | always hidden |

`hide_header_toolbar` works exactly like in the UI5 facade
(`UI5DataElementTrait::hasToolbarTop()`): an explicit value always wins, otherwise the toolbar is
only hidden if the header *and* the caption are hidden. Without this the cog button opening the
configurator would be unreachable on widgets with `hide_header: true`.

## Buttons in the header

The configurator adds three buttons to the search button group of the main toolbar. They are added
at position `0` one after another, so the resulting order is:

| Button | Added by | Purpose |
|---|---|---|
| collapse/expand | `addButtonToCollapseExpand()` | collapses the quick filter panel |
| cog | `addButtonToShowConfigurator()` | opens the configurator dialog |
| filter | `addButtonToToggleHeaderFilters()` (`EuiDataTable`) | toggles the datagrid filter row |

The cog button is only added if there is something to configure - see `hasConfiguratorDialog()`.
The collapse button is skipped when the filters were moved into the dialog.

## The configurator dialog

`buildHtmlConfiguratorDialog()` renders a hidden `<div>` with an `easyui-tabs` container inside.
The dialog itself is created **lazily** on the first click on the cog button in
`{prefix}ShowConfigurator()` - easyui moves a window into the `<body>`, so creating it up front
would be wasteful for widgets, that are never configured.

The controls inside the tabs on the other hand are initialized **at page load**, because their
values are read on every data request - no matter whether the dialog was ever opened.

### Tabs

| Tab | Condition | Control |
|---|---|---|
| Filters | `hide_header: true` and the filter tab has visible widgets | the regular filter widgets |
| Sorting | the widget has sortable columns or sorters | `$.fn.exfSorterBuilder` |
| Advanced search | the widget has filterable columns or filters | `$.fn.exfConditionBuilder` |

Widget setups are not implemented yet.

### Buttons

| Button | Behavior |
|---|---|
| Apply | closes the dialog and refreshes the data widget |
| Cancel | closes the dialog, keeping the current configuration |
| Reset | closes the dialog, restores the initial sorters, clears the advanced search, resets the filters and refreshes |

## Reading the configuration

| Aspect | Method | Request |
|---|---|---|
| filters | `JqueryDataConfiguratorTrait::buildJsDataGetter()` | `data.filters` |
| advanced search | `buildJsDataGetter()` override | appended to `data.filters.nested_groups` |
| sorters | `buildJsOnBeforeLoadAddSorters()` | `sortAttr` + `order` |

`buildJsOnBeforeLoadAddSorters()` deletes the datagrid's `sort` parameter when the configurator has
sorters, because `EuiDataTable::buildJsOnBeforeLoadAddConfiguratorData()` would otherwise overwrite
`sortAttr` with the column based sorting afterwards. In other words: as soon as the user configures
sorters explicitly, they replace the sorting of the column headers.

Both getters tolerate uninitialized controls (`.data('exfConditionBuilder') !== undefined`), so
unrendered configurators - e.g. inside an `InputComboTable` - keep working.

### Sorting tab vs. column headers

Both ways of sorting stay in sync, and whichever the user touched last wins:

| User action | What happens |
|---|---|
| click on a column header | the datagrid sends `sort`/`order`, `EuiDataTable` maps the column names to attribute aliases and pushes the result into the sorting tab via `buildJsSortersSetter()` |
| Apply / Reset in the dialog | `buildJsHeaderSortersReset()` clears `sortName` of the datagrid (and the sort arrows), so the next request has no `sort` and `buildJsOnBeforeLoadAddSorters()` sends the sorters of the tab as `sortAttr`/`order` |

`buildJsOnBeforeLoadAddSorters()` therefore never overrides an active column header sorting - it
only fills in `sortAttr`/`order` when `sort` is absent. `buildJsSortersSetter()` compares before
writing, so a refresh with unchanged sorting does not re-render the tab.

### Advanced search vs. column filters

The column filter row and the advanced search are two views on the same conditions:

| User action | What happens |
|---|---|
| a column filter changes | `EuiDataTable::buildJsOnBeforeLoadAddConfiguratorData()` passes the raw rules to `buildJsSearchConditionsSetter()`, which merges them into the top-most group of the advanced search |
| Apply in the dialog | `buildJsHeaderFiltersSet()` writes the conditions of the top-most group back into the filter row |
| Reset in the dialog | `buildJsHeaderFiltersReset()` removes all column filters |

Rules of the merge:

- Only the **top-most** condition group is touched and only if it is an **AND** group - column
  filters are always combined with AND and cannot be represented otherwise.
- If the group contains several rows for the same attribute, the **first** one is updated. The same
  holds the other way round: only the first condition per column reaches the filter row.
- Rows are removed when the corresponding column filter is cleared, but only rows for attributes,
  that actually have a column filter (`buildJsonForFilterableColumnAliases()`) and only if they have
  a value. Rows the user created for filter-only attributes or incomplete rows are never touched.
- Raw values are exchanged - exactly what the user typed. Both sides run the same data type parsers
  when building the request, so the conditions stay equivalent.

Because both sides are kept equal, the request contains the conditions twice (once from the filter
row, once from the advanced search). That is intentional: `A AND A` is equivalent to `A`, and it
keeps the filters working even if the advanced search is not rendered at all.

## The jEasyUI extensions

Both controls are own extensions in the style of jEasyUI plugins. They use regular easyui controls
(`combobox`, `textbox`, `linkbutton`) so they look like the rest of the UI, and they work with the
canonical ExFace data structures directly - there is no translation layer between the control and
the data format.

### `$.fn.exfConditionBuilder`

[`Facades/js/jeasyui/extensions/conditionbuilder/jquery.conditionbuilder.js`](../../Facades/js/jeasyui/extensions/conditionbuilder/jquery.conditionbuilder.js)

Renders **nested** condition groups with `AND`/`OR` operators (`EXF_LOGICAL_AND` /
`EXF_LOGICAL_OR`) - in contrast to the flat list of the UI5 advanced search. `getConditionGroup()`
returns a ready-to-use ExFace condition group or `null` if nothing is configured:

```json
{
    "operator": "AND",
    "ignore_empty_values": true,
    "conditions": [
        {"expression": "NAME", "comparator": "=", "value": "test", "object_alias": "my.App.OBJECT", "apply_to_aggregates": false}
    ],
    "nested_groups": [
        {"operator": "OR", "ignore_empty_values": true, "conditions": [], "nested_groups": []}
    ]
}
```

Values are normalized with `exfTools.data.filterComparator` and the data type parsers passed in the
`parsers` option (built from `buildJsFilterParser()` of the data type formatter) - exactly like the
header filters of a datagrid. `BETWEEN` is entered as a single `from..to` string.

Conditions without an expression or without a value and empty groups are skipped silently.

### `$.fn.exfSorterBuilder`

[`Facades/js/jeasyui/extensions/sorterbuilder/jquery.sorterbuilder.js`](../../Facades/js/jeasyui/extensions/sorterbuilder/jquery.sorterbuilder.js)

Renders a list of `{attribute_alias, direction}` with `ASC`/`DESC`. Since the order of the sorters
matters, every row can be moved up and down.

### Notes for extending

- Both plugins keep their state in a plain JS model and re-render completely on structural changes.
  The re-render is deferred via `setTimeout()` because it is triggered from handlers of the very
  controls it destroys.
- Comboboxes keep their drop-down panels in the `<body>`, so `destroy()` must be called on every
  control before a re-render - see `destroyControls()`.
- Options are merged with a **shallow** `$.extend()` on purpose: a deep extend would merge the
  default arrays (comparators, operators, directions) into the passed ones item by item.
- All controls have explicit pixel widths, so they can be initialized inside the closed dialog.

## Which fields are offered

| Control | Source |
|---|---|
| Advanced search | all filterable columns bound to an attribute (hidden ones only if they are the UID) plus all visible filters, sorted by caption |
| Sorting | the sorters of the widget first, then all sortable columns |

Filters over relations are rendered as `InputComboTable`, so the advanced search filters over the
label of the related object instead - see `getSearchableFields()`.
