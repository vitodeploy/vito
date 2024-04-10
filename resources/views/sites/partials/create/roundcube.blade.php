@include("sites.partials.create.fields.php-version")
<div>
    <x-input-label for="version" :value="__('Version')" />
    <x-select-input id="version" name="version" class="mt-1 w-full">
        <option value="" selected>{{ __("Select") }}</option>
        <option value="1.6.6" @if(old('version') == 'roundcubemail-1.6.6') selected @endif>Roundcube Mail 1.6.6</option>
    </x-select-input>
    @error("version")
    <x-input-error class="mt-2" :messages="$message" />
    @enderror
</div>

<div>
    <x-input-label for="title" :value="__('Support Url')" />
    <x-text-input
        value="{{ old('support_url') }}"
        id="support_url"
        name="support_url"
        type="text"
        class="mt-1 block w-full"
        autocomplete="branch"
    />
    @error("support_url")
        <x-input-error class="mt-2" :messages="$message" />
    @enderror
</div>
