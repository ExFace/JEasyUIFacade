# JEasyUIFacade
UI facade for the ExFace plattform based on jQuery EasyUI

## Browser regression checks

Open `Tests/JavaScript/datetime.php` through the application's web server.
If PHP execution under `vendor` is blocked, serve the vendor directory locally
with `php -S 127.0.0.1:8765 -t <vendor-directory>` and open
`http://127.0.0.1:8765/exface/jeasyuifacade/Tests/JavaScript/datetime.php`.
It uses the installed jQuery, EasyUI, Moment and core date utilities, and renders
the actual datetime editor options without a database. The page reports failures
or a passing assertion count for multi-row initialization, nullable and required
values, invalid input, date/time round-trips and unchanged/edited row tracking.
The installed EasyUI can be under `bower-asset/jeasyui` or `salt/jeasyui`.

Open `Tests/JavaScript/input-select.php` separately to check numeric/string enum
labels and stored keys, dropdown interaction, standalone and multiselect inputs,
and unchanged numeric/nullable select values. It renders the actual select editor
options without a database and uses the same local server setup.

Open `Tests/JavaScript/color-indicator.php` to check spacing for single-line and
wrapped colored labels, color-only swatches, column resizing and frozen-row
alignment. Datagrid color indicators retain a vertical inset as their rows grow.
Filled labels choose black or white text using the shared color contrast helper;
transparent backgrounds inherit the surrounding text color. The fixture checks
dynamic updates and a minimum 4.5:1 contrast ratio for opaque test backgrounds.

Editable datagrids initialize every configured editable row in yielding batches
(at most five rows, checking an eight-millisecond budget between rows).
An individual native `beginEdit` call cannot be interrupted.
The remaining load-success setup waits for completion; a loading mask reports
row progress and prevents interaction with partially initialized editors. The mask
stays inside the grid view so native layout preserves the row area, including
paginated grids fitted to their container. Rows, headers and the pager stay hidden
without changing their layout measurements until initialization finishes, then
appear together rather than rendering visibly in batches. Reloaded or destroyed grids
cancel stale batches. Matrix tables retain their existing editable-row predicate.
During initialization, unchanged-width resizes of the standard textbox,
numberbox, combobox and datetimebox editors are skipped. Normal resizing is
restored afterwards; custom editors are unaffected. EasyUI still scans open
editors for each row, so batching does not eliminate all quadratic work.
The regression page also checks yielding, deferred change
handlers, paginated row-area sizing during and after initialization,
reload/destruction cancellation and error propagation.
