<div>
    <x-primary-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'create-rule')">
        {{ __("Create new account") }}
    </x-primary-button>

    <x-modal name="create-rule">
        <form
            id="create-rule-form"
            hx-post="{{ route("servers.firewall.store", ["server" => $server]) }}"
            hx-swap="outerHTML"
            hx-select="#create-rule-form"
            class="p-6"
        >
            @csrf

            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __("Create new Account") }}
            </h2>

            <div class="mt-6 grid grid-cols-1 gap-3 lg:grid-cols-2">
                <div>
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input placeholder="{{ data_get($emailService, 'type_data.domain') }}" value="{{ old('email') }}" id="email" name="email" type="text" class="mt-1 w-full" />
                    @error("email")
                        <x-input-error class="mt-2" :messages="$message" />
                    @enderror
                </div>

                <div>
                    <x-input-label for="password" :value="__('Password')" />
                    <x-text-input value="{{ old('password') }}" id="password" name="password" type="text" class="mt-1 w-full" />
                    @error("password")
                        <x-input-error class="mt-2" :messages="$message" />
                    @enderror
                </div>
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
