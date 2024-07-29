<div>
    <x-primary-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'connect-provider')">
        {{ __("Connect") }}
    </x-primary-button>

    <x-modal name="connect-provider" :show="request()->has('provider')">
        @php
            $oldProvider = old("provider", request()->input("provider") ?? "");
        @endphp

        <form
            id="connect-provider-form"
            hx-post="{{ route("settings.server-providers.connect") }}"
            hx-swap="outerHTML"
            hx-select="#connect-provider-form"
            hx-ext="disable-element"
            hx-disable-element="#btn-connect-provider"
            class="p-6"
            x-data="{ provider: '{{ $oldProvider }}' }"
        >
            @csrf

            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __("Connect to a Server Provider") }}
            </h2>

            <div class="mt-6">
                <x-input-label for="provider" value="Provider" />
                <x-select-input x-model="provider" id="provider" name="provider" class="mt-1 w-full">
                    <option value="" selected disabled>
                        {{ __("Select") }}
                    </option>
                    @foreach (config("core.server_providers") as $p)
                        @if ($p !== "custom")
                            <option value="{{ $p }}" @if($oldProvider === $p) selected @endif>
                                {{ $p }}
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

            <div x-show="provider === 'aws'">
                <div class="mt-6">
                    <x-input-label for="key" value="Access Key" />
                    <x-text-input value="{{ old('key') }}" id="key" name="key" type="text" class="mt-1 w-full" />
                    @error("key")
                        <x-input-error class="mt-2" :messages="$message" />
                    @enderror
                </div>

                <div class="mt-6">
                    <x-input-label for="secret" value="Secret" />
                    <x-text-input
                        value="{{ old('secret') }}"
                        id="secret"
                        name="secret"
                        type="text"
                        class="mt-1 w-full"
                    />
                    @error("secret")
                        <x-input-error class="mt-2" :messages="$message" />
                    @enderror
                </div>
            </div>

            <div x-show="['hetzner', 'digitalocean', 'vultr', 'linode'].includes(provider)" class="mt-6">
                <x-input-label for="token" value="API Key" />
                <x-text-input value="{{ old('token') }}" id="token" name="token" type="text" class="mt-1 w-full" />
                @error("token")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

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

                <x-primary-button id="btn-connect-provider" class="ml-3">
                    {{ __("Connect") }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
