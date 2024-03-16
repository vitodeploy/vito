<x-modal name="create-database-user">
    <form
        id="create-database-user-form"
        hx-post="{{ route("servers.databases.users.store", $server) }}"
        hx-swap="outerHTML"
        hx-select="#create-database-user-form"
        hx-ext="disable-element"
        hx-disable-element="#btn-database-user-database"
        class="p-6"
        x-data="{ remote: @js((bool) old("remote", false)) }"
    >
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __("Create Database User") }}
        </h2>

        <div class="mt-6">
            <x-input-label for="user-username" :value="__('Username')" />
            <x-text-input
                value="{{ old('username') }}"
                id="user-username"
                name="username"
                type="text"
                class="mt-1 w-full"
            />
            @error("username")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

        <div class="mt-6">
            <x-input-label for="user-password" :value="__('Password')" />
            <x-text-input
                value="{{ old('password') }}"
                id="user-password"
                name="password"
                type="text"
                class="mt-1 w-full"
            />
            @error("password")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

        @if (in_array($server->database()?->name, config("core.database_features.remote")))
            <div class="mt-6">
                <label for="user-remote" class="inline-flex items-center">
                    <input
                        id="user-remote"
                        type="checkbox"
                        x-model="remote"
                        name="remote"
                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                    />
                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                        {{ __("Enable remote access") }}
                    </span>
                </label>
            </div>
        @endif

        <div x-show="remote">
            <div class="mt-6">
                <x-input-label for="user-host" :value="__('Host')" />
                <x-text-input value="{{ old('host') }}" id="user-host" name="host" type="text" class="mt-1 w-full" />
                <x-input-label
                    for="user-host"
                    :value="__('You might also need to open the database port in Firewall')"
                    class="mt-1"
                />
                @error("host")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>
        </div>

        <div class="mt-6 flex justify-end">
            <x-secondary-button type="button" x-on:click="$dispatch('close')">
                {{ __("Cancel") }}
            </x-secondary-button>

            <x-primary-button id="btn-database-user-database" class="ml-3">
                {{ __("Create") }}
            </x-primary-button>
        </div>
    </form>
</x-modal>
