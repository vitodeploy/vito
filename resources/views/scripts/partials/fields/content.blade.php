<x-input-label for="content" :value="__('Content')" />
<x-editor name="content" lang="sh" :value="$value" />
@error("content")
    <x-input-error class="mt-2" :messages="$message" />
@enderror

<x-input-help>You can use variables like ${VARIABLE_NAME} in the script</x-input-help>
<x-input-help>The variables will be asked when executing the script</x-input-help>
