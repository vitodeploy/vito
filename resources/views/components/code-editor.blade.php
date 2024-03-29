@props([
    "id",
    "name",
    "disabled" => false,
    "lang" => "text",
])

<div
    x-data="{
        editorId: @js($id),
        disabled: @js($disabled),
        lang: @js($lang),
        init() {
            let editor = null
            let theme =
                document.documentElement.className === 'dark'
                    ? 'one-dark'
                    : 'github'
            editor = window.ace.edit(this.editorId, {})
            let contentElement = document.getElementById(`text-${this.editorId}`)
            editor.setValue(contentElement.innerText, 1)
            if (this.disabled) {
                editor.setReadOnly(true)
            }
            editor.getSession().setMode(`ace/mode/${this.lang}`)
            editor.setTheme(`ace/theme/${theme}`)
            editor.setFontSize('15px')
            editor.setShowPrintMargin(false)
            editor.on('change', () => {
                contentElement.innerHTML = editor.getValue()
            })
            document.body.addEventListener('color-scheme-changed', (event) => {
                theme = event.detail.theme === 'dark' ? 'one-dark' : 'github'
                editor.setTheme(`ace/theme/${theme}`)
            })
        },
    }"
>
    <div id="{{ $id }}" class="min-h-[400px] w-full rounded-md border border-gray-200 dark:border-gray-700"></div>
    <textarea id="text-{{ $id }}" name="{{ $name }}" class="hidden">{{ $slot }}</textarea>
</div>
