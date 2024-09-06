@use(App\Enums\StorageProvider)
@php
    $storageProviders = [
        StorageProvider::DROPBOX,
        StorageProvider::FTP,
        StorageProvider::LOCAL,
        StorageProvider::S3,
        StorageProvider::WASABI,
    ];
@endphp

<div>
    <x-primary-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'connect-provider')">
        {{ __("Connect") }}
    </x-primary-button>

    <x-modal name="connect-provider">
        @php
            $provider = old("provider", request()->input("provider") ?? \App\Enums\StorageProvider::DROPBOX);
        @endphp

        <form
            id="connect-storage-provider-form"
            hx-post="{{ route("settings.storage-providers.connect") }}"
            hx-swap="outerHTML"
            hx-select="#connect-storage-provider-form"
            hx-ext="disable-element"
            hx-disable-element="#btn-connect-storage-provider"
            class="p-6"
            x-data="{
                provider: '{{ $provider }}',
                init() {
                    $watch('provider', (value) => {
                        htmx.ajax(
                            'GET',
                            '{{ route("settings.storage-providers") }}?provider=' +
                                this.provider,
                            {
                                target: '#connect-storage-provider-form',
                                swap: 'outerHTML',
                                select: '#connect-storage-provider-form',
                            },
                        )
                    })
                },
            }"
        >
            @csrf
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __("Connect to a Storage Provider") }}
            </h2>

            <div class="mt-6">
                <x-input-label for="provider" value="Provider" />
                <x-select-input x-model="provider" id="provider" name="provider" class="mt-1 w-full">
                    <option value="" selected disabled>
                        {{ __("Select") }}
                    </option>
                    @foreach (config("core.storage_providers") as $p)
                        @if ($p !== "custom")
                            <option value="{{ $p }}" @if($provider === $p) selected @endif>
                                {{ $p }}
                                @if ($p === "ftp")
                                    (Beta)
                                @endif
                            </option>
                        @endif
                    @endforeach
                </x-select-input>
                @error("provider")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6">
                <x-input-label for="name" value="Name" />
                <x-text-input value="{{ old('name') }}" id="name" name="name" type="text" class="mt-1 w-full" />
                @error("name")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            @if (in_array($provider, $storageProviders))
                @include("settings.storage-providers.providers.{$provider}")
            @endif

            <div class="mt-6">
                <x-checkbox id="global" name="global" :checked="old('global')" value="1">
                    Is Global (Accessible in all projects)
                </x-checkbox>
                @error("global")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    {{ __("Cancel") }}
                </x-secondary-button>

                <x-primary-button id="btn-connect-storage-provider" class="ml-3">
                    {{ __("Connect") }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
