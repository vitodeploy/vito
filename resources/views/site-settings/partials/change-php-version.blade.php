<x-card>
    <x-slot name="title">{{ __("Change PHP Version") }}</x-slot>

    <x-slot name="description">
        {{ __("You can change your site's PHP version here") }}
    </x-slot>

    <form
        id="change-php-version"
        hx-post="{{ route("servers.sites.settings.php", ["server" => $server, "site" => $site]) }}"
        hx-swap="outerHTML"
        hx-select="#change-php-version"
        hx-ext="disable-element"
        hx-disable-element="#btn-change-php-version"
        class="space-y-6"
    >
        <div>
            <x-input-label for="version" :value="__('PHP Version')" />
            <x-select-input id="version" name="version" class="mt-1 w-full">
                <option value="" disabled selected>{{ __("Select") }}</option>
                @foreach ($site->server->installedPHPVersions() as $php)
                    <option value="{{ $php }}" @if($php == old('version', $site->php_version)) selected @endif>
                        {{ $php }}
                    </option>
                @endforeach
            </x-select-input>
            @error("version")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>
    </form>

    <x-slot name="actions">
        <x-primary-button id="btn-change-php-version" form="change-php-version" hx-disable>
            {{ __("Save") }}
        </x-primary-button>
    </x-slot>
</x-card>
