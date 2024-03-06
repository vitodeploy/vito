<div>
    <x-input-label for="web_directory" :value="__('Web Directory')" />
    <x-text-input
        value="{{ old('web_directory') }}"
        id="web_directory"
        name="web_directory"
        type="text"
        class="mt-1 block w-full"
        autocomplete="web_directory"
    />
    <x-input-help>
        {{ __("For root, leave this blank") }}
    </x-input-help>
    @error("web_directory")
        <x-input-error class="mt-2" :messages="$message" />
    @enderror
</div>
