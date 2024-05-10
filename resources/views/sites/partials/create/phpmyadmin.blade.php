@include("sites.partials.create.fields.php-version")

<div>
    <x-input-label for="version" :value="__('Version')" />
    <x-select-input id="version" name="version" class="mt-1 w-full">
        <option value="" selected>{{ __("Select") }}</option>
        <option value="5.1.2" @if(old('version') == '5.1.2') selected @endif>PHPMyAdmin 5.1.2</option>
    </x-select-input>
    @error("version")
        <x-input-error class="mt-2" :messages="$message" />
    @enderror
</div>
