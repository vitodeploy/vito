<div x-data="{ type: '{{ old("type") }}' }">
    <x-primary-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'create-ssl')">
        {{ __("Create SSL") }}
    </x-primary-button>

    <x-modal name="create-ssl">
        <form
            id="create-ssl-form"
            hx-post="{{ route("servers.sites.ssl.store", ["server" => $server, "site" => $site]) }}"
            hx-swap="outerHTML"
            hx-select="#create-ssl-form"
            class="p-6"
        >
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __("Create SSL") }}
            </h2>

            <div class="mt-6">
                <x-input-label for="type" :value="__('SSL Type')" />
                <x-select-input x-model="type" id="type" name="type" class="mt-1 w-full">
                    <option value="" selected disabled>
                        {{ __("Select") }}
                    </option>
                    @foreach (config("core.ssl_types") as $t)
                        <option value="{{ $t }}" @if($t == old('type')) selected @endif>
                            {{ $t }}
                        </option>
                    @endforeach
                </x-select-input>
                @error("type")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div x-show="type === 'custom'">
                <div class="mt-6">
                    <x-input-label for="certificate" :value="__('Certificate')" />
                    <x-textarea
                        value="{{ old('certificate') }}"
                        id="certificate"
                        name="certificate"
                        type="text"
                        class="mt-1 w-full"
                        rows="5"
                    >
                        {{ old("certificate") }}
                    </x-textarea>
                    @error("certificate")
                        <x-input-error class="mt-2" :messages="$message" />
                    @enderror
                </div>

                <div class="mt-6">
                    <x-input-label for="private" :value="__('Private Key')" />
                    <x-textarea
                        value="{{ old('private') }}"
                        id="private"
                        name="private"
                        type="text"
                        class="mt-1 w-full"
                        rows="5"
                    >
                        {{ old("private") }}
                    </x-textarea>
                    @error("private")
                        <x-input-error class="mt-2" :messages="$message" />
                    @enderror
                </div>

                <div class="mt-6">
                    <x-input-label for="expires_at" :value="__('Expires At')" />
                    <x-text-input
                        value="{{ old('expires_at') }}"
                        id="expires_at"
                        name="expires_at"
                        type="text"
                        class="mt-1 w-full"
                    />
                    @error("expires_at")
                        <x-input-error class="mt-2" :messages="$message" />
                    @enderror
                </div>
            </div>

            <div class="mt-6">
                <x-checkbox id="aliases" name="aliases" :checked="old('aliases')" value="1">
                    Set SSL for site's aliases as well
                </x-checkbox>
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    {{ __("Cancel") }}
                </x-secondary-button>

                <x-primary-button class="ml-3" hx-disable>
                    {{ __("Create") }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
