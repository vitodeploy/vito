<x-card>
    <x-slot name="title">{{ __("Change PHP Version") }}</x-slot>

    <x-slot name="description">
        {{ __("You can change your site's PHP version here") }}
    </x-slot>

    <form id="change-php-version" wire:submit="change" class="space-y-6">
        <div>
            <x-input-label for="version" :value="__('PHP Version')" />
            <x-select-input
                wire:model="version"
                id="version"
                name="version"
                class="mt-1 w-full"
            >
                <option value="" disabled selected>{{ __("Select") }}</option>
                @foreach ($site->server->installedPHPVersions() as $php)
                    <option
                        value="{{ $php }}"
                        @if($php === $version) selected @endif
                    >
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
        @if (session("status") === "changing-php-version")
            <p class="mr-2">{{ __("Updating...") }}</p>
        @endif

        @if (session("status") === "change-site-php-finished")
            <p class="mr-2">{{ __("Updated!") }}</p>
        @endif

        @if (session("status") === "change-site-php-failed")
            <p class="mr-2">{{ __("Failed!") }}</p>
        @endif

        <x-primary-button
            form="change-php-version"
            wire:loading.attr="disabled"
        >
            {{ __("Save") }}
        </x-primary-button>
    </x-slot>
</x-card>
