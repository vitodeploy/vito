import ace from 'brace';
import 'brace/mode/javascript';
import 'brace/mode/plain_text';
import 'brace/mode/sh';
import 'brace/mode/ini';
import 'brace/ext/searchbox'
import './theme-vito'
import './mode-env';
import './mode-nginx';

window.initAceEditor = function (options = {}) {
    const editorValue = JSON.parse(options.value || '');
    const editor = ace.edit(options.id);
    editor.setTheme("ace/theme/vito");
    editor.getSession().setMode(`ace/mode/${options.lang || 'plain_text'}`);
    editor.setValue(editorValue, -1);
    editor.clearSelection();
    editor.focus();
    editor.setOptions({
        enableBasicAutocompletion: true,
        enableSnippets: true,
        enableLiveAutocompletion: true,
        printMargin: false,
    });

    editor.renderer.setScrollMargin(15, 15, 0, 0)
    editor.renderer.setPadding(15);

    editor.getSession().on('change', function () {
        document.getElementById(`textarea-${options.id}`).value = editor.getValue();
    });

    window.addEventListener('resize', function () {
        editor.resize();
    })

    document.getElementById(`textarea-${options.id}`).innerHTML = editorValue;

    return editor;
}
