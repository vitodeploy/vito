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
    <x-input-label for="imap_host" :value="__('Imap Host')" />
    <x-text-input
        value="{{ old('imap_host') }}"
        id="imap_host"
        name="imap_host"
        type="text"
        class="mt-1 block w-full"
        placeholder="example.com:143"
    />
    @error("imap_host")
    <x-input-error class="mt-2" :messages="$message" />
    @enderror
</div>

<div>
    <x-input-label for="smtp_host" :value="__('Smtp Host')" />
    <x-text-input
        value="{{ old('smtp_host') }}"
        id="smtp_host"
        name="smtp_host"
        type="text"
        class="mt-1 block w-full"
        placeholder="example.com:25"
    />
    @error("smtp_host")
    <x-input-error class="mt-2" :messages="$message" />
    @enderror
</div>

<div>
    <x-input-label for="support_url" :value="__('Support Url')" />
    <x-text-input
        value="{{ old('support_url') }}"
        id="support_url"
        name="support_url"
        type="text"
        class="mt-1 block w-full"
    />
    @error("support_url")
        <x-input-error class="mt-2" :messages="$message" />
    @enderror
</div>
