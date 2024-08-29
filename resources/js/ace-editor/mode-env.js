ace.define("ace/mode/env", ["require", "exports", "module", "ace/lib/oop", "ace/mode/text", "ace/mode/env_highlight_rules", "ace/mode/folding/ini","ace/mode/behaviour"],  function (require, exports) {
    var oop = require("../lib/oop");
    var TextMode = require("./text").Mode;
    var Behaviour = require("./behaviour").Behaviour;
    var envHighlightRules = require("./env_highlight_rules").envHighlightRules;

    var Mode = function () {
        this.HighlightRules = envHighlightRules;
        this.$behaviour = new Behaviour
    };

    oop.inherits(Mode, TextMode);

    (function() {
            this.lineCommentStart = "#",
            this.blockComment = null,
            this.$id = "ace/mode/env"
    }).call(Mode.prototype),

    exports.Mode = Mode;
})
ace.define("ace/mode/env_highlight_rules", ["require", "exports", "module", "ace/lib/oop", "ace/mode/text_highlight_rules"], function (require, exports, module) {
    "use strict";

    var oop = require("../lib/oop");
    var TextHighlightRules =
        require("./text_highlight_rules").TextHighlightRules;

    var envHighlightRules = function () {
        this.$rules = {
            start: [
                {
                    token: "punctuation.definition.comment.env",
                    regex: "#.*",
                    push_: [
                        {
                            token: "comment.line.number-sign.env",
                            regex: "$|^",
                            next: "pop",
                        },
                        {
                            defaultToken: "comment.line.number-sign.env",
                        },
                    ],
                },
                {
                    token: "punctuation.definition.comment.env",
                    regex: "#.*",
                    push_: [
                        {
                            token: "comment.line.semicolon.env",
                            regex: "$|^",
                            next: "pop",
                        },
                        {
                            defaultToken: "comment.line.semicolon.env",
                        },
                    ],
                },
                {
                    token: [
                        "keyword.other.definition.env",
                        "text",
                        "punctuation.separator.key-value.env",
                    ],
                    regex: "\\b([a-zA-Z0-9_.-]+)\\b(\\s*)(=)",
                },
                {
                    token: [
                        "punctuation.definition.entity.env",
                        "constant.section.group-title.env",
                        "punctuation.definition.entity.env",
                    ],
                    regex: "^(\\[)(.*?)(\\])",
                },
                {
                    token: "punctuation.definition.string.begin.env",
                    regex: "'",
                    push: [
                        {
                            token: "punctuation.definition.string.end.env",
                            regex: "'",
                            next: "pop",
                        },
                        {
                            token: "constant.language.escape",
                            regex: "\\\\(?:[\\\\0abtrn;#=:]|x[a-fA-F\\d]{4})",
                        },
                        {
                            defaultToken: "string.quoted.single.env",
                        },
                    ],
                },
                {
                    token: "punctuation.definition.string.begin.env",
                    regex: '"',
                    push: [
                        {
                            token: "constant.language.escape",
                            regex: "\\\\(?:[\\\\0abtrn;#=:]|x[a-fA-F\\d]{4})",
                        },
                        {
                            token: "support.constant.color",
                            regex: /\${[\w]+}/,
                        },
                        {
                            token: "punctuation.definition.string.end.env",
                            regex: '"',
                            next: "pop",
                        },
                        {
                            defaultToken: "string.quoted.double.env",
                        },
                    ],
                },
                {
                    token: "constant.language.boolean",
                    regex: /(?:true|false)\b/,
                },
            ],
        };
        this.normalizeRules();
    };

    envHighlightRules.metaData = {
        fileTypes: ["env"],
        keyEquivalent: "^~I",
        name: "Env",
        scopeName: "source.env",
    };

    oop.inherits(envHighlightRules, TextHighlightRules);

    exports.envHighlightRules = envHighlightRules;
});

