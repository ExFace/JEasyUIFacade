# Icons in the jEasyUI facade

## Card and panel icons

Cards, panels and forms support Font Awesome icon names and inline SVG in their
headers. Set `icon` to the SVG markup; SVG icons are detected automatically.
Use `icon_set: "svg"` to match the header text color, or
`icon_set: "svg_colored"` to keep colors defined inside the SVG.
SVG icons are sized to fit the header icon area. Setting `hide_caption: true`
hides the whole header, including its icon.

## Menu item icons

Menu items support the same SVG settings as cards. Set `icon` and, optionally,
`icon_set` on each button in the menu. Font icons, monochrome SVG and colored SVG
can be mixed in one menu. SVG icons fit the menu's icon area and monochrome icons
follow the item text color, including on hover.

This works for both inline menus and the dropdown items of a MenuButton.
Set `show_icon: false` on a button to hide its icon without hiding its caption.