import ace from "brace";
import "brace/mode/ini";
import "brace/ext/searchbox";
import "../ace-editor/theme-vito";
import "../ace-editor/mode-env";
import "../ace-editor/mode-nginx";

export default (Alpine) => {
    Alpine.data("codeEditorFormComponent", ({ state, options }) => {
        return {
            state,
            options,
            init: function () {
                this.render();
            },
            render() {
                this.editor = null;

                const editorValue = JSON.parse(this.options.value || "");
                this.editor = ace.edit(this.options.id);
                this.editor.$blockScrolling = Infinity;
                this.editor.setTheme("ace/theme/vito");
                this.editor.setValue(editorValue, -1);
                this.editor
                    .getSession()
                    .setMode(`ace/mode/${this.options.lang || "plain_text"}`);
                this.editor.clearSelection();
                this.editor.focus();
                this.editor.setOptions({
                    printMargin: false,
                });

                this.editor.renderer.setScrollMargin(15, 15, 0, 0);
                this.editor.renderer.setPadding(15);

                this.editor.getSession().on("change", () => {
                    this.state = this.editor.getValue();
                });

                window.addEventListener("resize", () => {
                    this.editor.resize();
                });

                this.state = editorValue;
            },
        };
    });
};
