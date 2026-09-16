# jEasyUI data configurator

The `EuiDataConfigurator` facade element is the jEasyUI implementation of the Core
`DataConfigurator` widget model. It generates the personalization UI of a data widget in two
places:

1. the **header panel** above the data widget - the quick filters and the toolbar with the buttons,
2. the **configurator dialog** - a tabbed jEasyUI dialog with sorting, advanced search and (if the
   header is hidden permanently) the filters.

Everything belonging to the configurator lives in
[`Facades/Elements/EuiDataConfigurator.php`](../../Facades/Elements/EuiDataConfigurator.php). The
data widget facade elements only call into it: `EuiData::buildHtmlTableHeader()` generates the
header HTML and adds the buttons, while `EuiDataTable::buildJs()` and
`EuiDataElementTrait::buildJsForPanel()` generate the JavaScript via `buildJsForPanelHeader()`.

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
| default, desktop | visible | visible |
| default, smartphone (up to 600px) | in the configurator dialog | visible |
| `hide_header: false` | visible on every screen size | visible |
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
| cog | `addButtonsToSearchGroup()` | opens the configurator dialog |
| filter | `addButtonToToggleHeaderFilters()` (`EuiDataTable`) | toggles the datagrid filter row |

The cog button is only added if there is something to configure - see `hasConfiguratorDialog()`.
The collapse button is skipped when the filters were moved into the dialog permanently. For the
responsive default it remains available on desktop and is hidden on smartphones together with the
quick-filter panel.

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
| Setups | `DataTableConfigurator::hasSetups()` for a regular `EuiDataTable` | Core-provided setups table |

## Widget setups

### Scope and entry points

Widget setups are supported only by the regular `EuiDataTable` facade element. Other `EuiData`
facade element descendants, including spreadsheets, charts and maps, do not inherit the setup
implementation. This keeps each future widget type free to implement the payload defined by its
own setup prototype.

The setups table is initialized without autoloading and refreshed whenever the configurator dialog
opens. Its Save, Update, Apply and Delete actions call the DataTable widget functions implemented by
`EuiDataTableSetupTrait`.

### Where the implementation lives

The setup implementation is split deliberately between the facade-independent Core widget and
mutation models, the jEasyUI facade elements and the browser-side setup manager:

| Layer | Source | Responsibility |
|---|---|---|
| Widget interface | [`iSupportWidgetSetups.php`](../../../core/Interfaces/Widgets/iSupportWidgetSetups.php) | Identifies configurator widget models that expose setups and their setups table. |
| Configurator widget model | [`DataTableConfigurator.php`](../../../core/Widgets/DataTableConfigurator.php) | Builds the setups tab, table, filters and Save, Update, Apply and Delete action models. |
| Setup mutation model | [`DataTableSetup.php`](../../../core/Mutations/Prototypes/DataTableSetup.php) and [`Mutations/MutationRules/`](../../../core/Mutations/MutationRules/) | Defines and validates the facade-independent columns, Advanced Search condition group and sorters payload. |
| jEasyUI configurator facade element | [`EuiDataConfigurator.php`](../../Facades/Elements/EuiDataConfigurator.php) | Generates the setups tab, refreshes its table and projects the active setup marker. |
| jEasyUI DataTable facade element | [`EuiDataTable.php`](../../Facades/Elements/EuiDataTable.php) | Gates setup support to regular DataTables, loads the required scripts and starts auto-apply. |
| jEasyUI shared setup logic | [`EuiDataTableSetupTrait.php`](../../Facades/Elements/Traits/EuiDataTableSetupTrait.php) | emits the JavaScript for `dump_setup`, `apply_setup` and clearing the current setup |
| jEasyUI browser state | [`exfSetupManager.js`](../../Facades/js/exfSetupManager.js) | translates between jEasyUI controls and setup JSON, applies setups and stores the active setup in IndexedDB |

The Core widget models decide whether setups are available and construct their child widget and
action models. The jEasyUI facade elements generate the controls and route widget functions to
JavaScript. That JavaScript reads and writes the live configurator controls, converts sorter
directions where necessary, persists the last applied setup and updates the client-only active
marker.

### Shared setup model

The JSON stored in a setup is a **facade-neutral model**, not a serialization of jEasyUI controls.
It follows the Core `DataTableSetup` UXON contract and must not persist facade-specific control IDs,
widget structures or value conventions. jEasyUI uses the nested `advanced_conditions` model; UI5
temporarily continues to use the legacy flat `advanced_search` model until it is migrated.

The jEasyUI implementation therefore uses the same `DataTableSetup` UXON payload and the same local
identity triple (`slug`, `widget_id`, `object_id`) as UI5. It currently captures and restores:

- sorting from the sorter builder;
- one complete `advanced_conditions` ConditionGroup from Advanced Search, including its operator,
  conditions and recursively nested groups;
- available column visibility state, including the legacy `attribute_alias` lookup when applying.

Advanced Search is the canonical filter state shared by setups and column-header filters. The
complete group is stored using `AdvancedConditionGroupSetupRule`:

| Interaction | Result |
|---|---|
| change a column-header filter | merges its comparator and value into the matching condition in Advanced Search |
| change Advanced Search and apply the dialog | updates every matching column-header filter from the **first** condition with the same `attribute_alias`, including both comparator and value |
| save or update a setup | stores Advanced Search; column-header filters are **not** stored separately |
| load a setup | restores Advanced Search first, then derives matching column-header filters from its first matching conditions |

Consequently, setup payloads have one source of truth for filtering. A column-header filter first
updates Advanced Search, which is what the setup persists. Loading performs the reverse projection:
the saved Advanced Search model is installed and matching column headers receive the comparator and
value of the first condition for their attribute.

Column order is left unchanged because the current Core setup rules and jEasyUI DataTable
personalization UI do not support it yet.

The last explicitly applied setup is stored in IndexedDB and automatically restored when the table
is initialized. Reset removes that local preference, and deleting the currently applied setup resets
the table as well. The storage implementation is in
[`Facades/js/exfSetupManager.js`](../../Facades/js/exfSetupManager.js).

The setups table's `SETUP_APPLIED` column is client-only. After every table load,
`markCurrentSetupAsActive()` compares each row UID with the active setup UID in IndexedDB and places
a check icon on the matching row. Applying another setup moves the marker without reloading data.
Saving a new setup reloads the setups table once because the newly created row is not yet present in
the client model, after which the normal load hook marks it.

The setup quick-select controls in the table caption and configurator split button are rendered by
`exfSetupManager.quickSelect`. Both menus use the rows of that same setups table, including its
client-only active marker, and trigger its lazy load only once. Opening the configurator after a
quick-select menu therefore does not make a second setup request. Favorites are sorted first by the
setups table model, so the configurator and both quick-select menus keep the same order. Clicking a
setup applies it directly; hovering it opens a submenu for Apply, Update and Edit. Update and Edit
resolve and select the current shared setups-table row by UID before invoking the existing
configurator buttons, keeping their validation and action behavior in one place even if the menu
was rendered before the table reloaded.

## Dialog buttons

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

Before serializing a request, `EuiDataTable::buildJsOnBeforeLoadAddConfiguratorData()` copies a
column-header sort into the sorter builder and removes the datagrid's native `sort`/`order`
parameters. `buildJsOnBeforeLoadAddSorters()` then serializes the canonical sorter-builder state as
`sortAttr`/`order`.

Both getters tolerate uninitialized controls (`.data('exfConditionBuilder') !== undefined`), so
unrendered configurators - e.g. inside an `InputComboTable` - keep working.

### Sorting tab vs. column headers

Both ways of sorting stay in sync, and whichever the user touched last wins:

| User action | What happens |
|---|---|
| click on a column header | the datagrid sends `sort`/`order`, `EuiDataTable` maps the column names to attribute aliases and pushes the result into the sorting tab via `buildJsSortersSetter()` |
| Apply / Reset in the dialog | `buildJsHeaderSortersReset()` clears `sortName` of the datagrid (and the sort arrows), so the next request has no `sort` and `buildJsOnBeforeLoadAddSorters()` sends the sorters of the tab as `sortAttr`/`order` |

`buildJsSortersSetter()` compares before writing, so a refresh with unchanged sorting does not
re-render the tab.

### Advanced search vs. column filters

The column filter row and Advanced Search are two views on the same conditions. Advanced Search is
the canonical model; column filters are a compact projection for attributes represented by table
columns:

| User action | What happens |
|---|---|
| a column filter changes | `EuiDataTable::buildJsOnBeforeLoadAddConfiguratorData()` passes the raw rules to `buildJsSearchConditionsSetter()`, which merges them into the top-most group of the advanced search |
| Apply in the dialog | `buildJsHeaderFiltersSet()` writes the comparator and value of the first matching condition per attribute back into the filter row |
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

Before serializing a request, header rules are merged into Advanced Search and the native
`filterRules` parameter is removed. The request therefore contains each condition only once, from
the canonical Advanced Search model. Setups likewise persist only Advanced Search, never a separate
copy of the header filters. Applying a setup replaces that model and then updates matching column
filters, including comparator and value, from the first condition for each attribute.

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