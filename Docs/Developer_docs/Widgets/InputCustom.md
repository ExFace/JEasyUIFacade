# InputCustom widget in jEasyUI

When an `InputCustom` widget has `buttons` or `toolbars`, jEasyUI renders it as a native panel. The toolbar sits above the custom content and uses the same styling as a table toolbar. The panel has no outer form-input padding.

Set `caption` to show a title in the panel header. Omit it if the surrounding panel already has a title, or use `hide_caption: true` to hide a configured caption.

Without a toolbar, the widget keeps its standard form layout and displays its caption as a form label.