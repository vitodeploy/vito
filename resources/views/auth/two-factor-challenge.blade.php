<x-guest-layout>
    <div x-data="{recover: @if($errors->has('recovery_code')) true @else false @endif}">
        <div x-show="recover">
            <form method="POST">
                @csrf
                <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                    {{ __("Please enter your recovery code") }}
                </div>

                <div>
                    <x-input-label for="recovery_code" :value="__('Recovery Code')" />
                    <x-text-input
                        id="recovery_code"
                        class="mt-1 block w-full"
                        type="text"
                        name="recovery_code"
                        required
                        autofocus
                        autocomplete="recovery_code"
                    />
                    <x-input-error :messages="$errors->get('recovery_code')" class="mt-2" />
                </div>

                <div class="mt-4 flex items-center justify-end">
                    <x-secondary-button class="mr-2" x-on:click="recover = false">
                        {{ __("Login") }}
                    </x-secondary-button>
                    <x-primary-button type="submit">
                        {{ __("Recover") }}
                    </x-primary-button>
                </div>
            </form>
        </div>
        <div x-show="!recover">
            <form method="POST">
                @csrf
                <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                    {{ __("Please confirm access to your account by entering the authentication code provided by your authenticator application.") }}
                </div>

                <div>
                    <x-input-label for="code" :value="__('Code')" />
                    <x-text-input
                        id="code"
                        class="mt-1 block w-full"
                        type="text"
                        name="code"
                        required
                        autofocus
                        autocomplete="code"
                    />
                    <x-input-error :messages="$errors->get('code')" class="mt-2" />
                </div>

                <div class="mt-4 flex items-center justify-end">
                    <x-secondary-button class="mr-2" x-on:click="recover = true">
                        {{ __("Recover") }}
                    </x-secondary-button>
                    <x-primary-button type="submit">
                        {{ __("Login") }}
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
