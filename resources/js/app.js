import CodeEditorAlpinePlugin from "./components/editor";

document.addEventListener("alpine:init", () => {
    window.Alpine.plugin(CodeEditorAlpinePlugin);
});

window.copyToClipboard = async function (text) {
    try {
        await navigator.clipboard.writeText(text);
    } catch (err) {
        const textArea = document.createElement("textarea");
        textArea.value = text;

        textArea.style.position = "absolute";
        textArea.style.left = "-999999px";

        document.body.prepend(textArea);
        textArea.select();

        try {
            document.execCommand("copy");
        } catch (error) {
            //
        } finally {
            textArea.remove();
        }
    }
};
