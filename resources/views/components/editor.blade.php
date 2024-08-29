<div>
    <div
        id="{{ $id }}"
        {{ $attributes->merge(["class" => "mt-1 min-h-[400px] w-full"]) }}
        class="ace-vito ace_dark"
    ></div>
    <textarea id="textarea-{{ $id }}" name="{{ $name }}" style="display: none"></textarea>
    <script>
        if (window.initAceEditor) {
            window.initAceEditor(@json($options));
        } else {
            document.addEventListener('DOMContentLoaded', function () {
                window.initAceEditor(@json($options));
            });
        }
    </script>
</div>
