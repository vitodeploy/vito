<x-dynamic-component :component="$getFieldWrapperView()" :field="$field" :label-sr-only="$isLabelHidden()">
    <div
        wire:ignore
        x-data="codeEditorFormComponent({
                    state: $wire.{{ $applyStateBindingModifiers('entangle(\'' . $getStatePath() . '\')') }},
                    options: @js($getOptions()),
                })"
    >
        <div>
            <div
                id="{{ $getId() }}"
                {{ $attributes->merge(["class" => "mt-1 min-h-[400px] w-full border border-gray-100 dark:border-gray-700"]) }}
                class="ace-vito ace_dark"
            ></div>
            <textarea id="textarea-{{ $getId() }}" style="display: none" x-model="state"></textarea>
        </div>
    </div>
</x-dynamic-component>
