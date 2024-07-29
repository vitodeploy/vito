<x-input-label for="content" :value="__('Content')" />
<x-textarea id="content" name="content" class="mt-1 min-h-[400px] w-full font-mono">
    {{ $value }}
</x-textarea>
@error("content")
    <x-input-error class="mt-2" :messages="$message" />
@enderror

<x-input-help>You can use variables like ${VARIABLE_NAME} in the script</x-input-help>
<x-input-help>The variables will be asked when executing the script</x-input-help>
