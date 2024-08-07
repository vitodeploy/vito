ace.define(
    "ace/theme/vito",
    ["require", "exports", "module", "ace/lib/dom"],
    function (require, exports) {
        (exports.isDark = true),
            (exports.cssClass = "ace-vito rounded-lg w-full"),
            (exports.cssText = `
        .ace-vito .ace_scrollbar::-webkit-scrollbar {  width: 12px;}
        .ace-vito .ace_scrollbar::-webkit-scrollbar-track {  background: #111827;}
        .ace-vito .ace_scrollbar::-webkit-scrollbar-thumb {  background: #374151;  border-radius: 4px;}
        .ace-vito .ace_gutter {background: #151c27;color: rgb(128,145,160)}
        .ace-vito .ace_print-margin {width: 1px;background: #555555}
        .ace-vito {background-color: #0f172a;color: #F9FAFB}
        .ace-vito .ace_cursor {color: #F9FAFB}
        .ace-vito .ace_marker-layer .ace_selection {background: rgba(179, 101, 57, 0.75)}
        .ace-vito.ace_multiselect .ace_selection.ace_start {box-shadow: 0 0 3px 0px #002240;}
        .ace-vito .ace_marker-layer .ace_step {background: rgb(127, 111, 19)}
        .ace-vito .ace_marker-layer .ace_bracket {margin: -1px 0 0 -1px;border: 1px solid rgba(255, 255, 255, 0.15)}
        .ace-vito .ace_marker-layer .ace_active-line {background: rgba(24, 182, 155, 0.10)}
        .ace-vito .ace_gutter-active-line {background-color: rgba(0, 0, 0, 0.35)}
        .ace-vito .ace_marker-layer .ace_selected-word {border: 1px solid rgba(179, 101, 57, 0.75)}
        .ace-vito .ace_invisible {color: rgba(255, 255, 255, 0.15)}
        .ace-vito .ace_keyword,.ace-vito .ace_meta {color: #FF9D00}
        .ace-vito .ace_constant,.ace-vito .ace_constant.ace_character,.ace-vito .ace_constant.ace_character.ace_escape,.ace-vito .ace_constant.ace_other {color: #FF628C}
        .ace-vito .ace_invalid {color: #F8F8F8;background-color: #800F00}
        .ace-vito .ace_support {color: #80FFBB}
        .ace-vito .ace_support.ace_constant {color: #EB939A}
        .ace-vito .ace_fold {background-color: #FF9D00;border-color: #F9FAFB}
        .ace-vito .ace_support.ace_function {color: #FFB054}
        .ace-vito .ace_storage {color: #FFEE80}
        .ace-vito .ace_entity {color: #FFDD00}
        .ace-vito .ace_string {color: #7cd827}
        .ace-vito .ace_string.ace_regexp {color: #80FFC2}
        .ace-vito .ace_comment {font-style: italic;color: #6B7280}
        .ace-vito .ace_heading,.ace-vito
        .ace_markup.ace_heading {color: #C8E4FD;background-color: #001221}
        .ace-vito .ace_list,.ace-vito .ace_markup.ace_list {background-color: #130D26}
        .ace-vito .ace_variable {color: #CCCCCC}
        .ace-vito .ace_variable.ace_language {color: #FF80E1}
        .ace-vito .ace_meta.ace_tag {color: #9EFFFF}
        .ace-vito .ace_indent-guide {background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAACCAYAAACZgbYnAAAAEklEQVQImWNgYGBgYHCLSvkPAAP3AgSDTRd4AAAAAElFTkSuQmCC) right repeat-y}
    `);

        var dom = require("../lib/dom");
        dom.importCssString(exports.cssText, exports.cssClass);
    },
);
