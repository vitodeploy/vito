<x-modal name="create-database">
    <form
        id="create-database-form"
        hx-post="{{ route("servers.databases.store", $server) }}"
        hx-swap="outerHTML"
        hx-select="#create-database-form"
        hx-ext="disable-element"
        hx-disable-element="#btn-create-database"
        class="p-6"
        x-data="{ user: @js((bool) old("user", false)), remote: @js((bool) old("remote", false)) }"
    >
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __("Create Database") }}
        </h2>

        <div class="mt-6">
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input value="{{ old('name') }}" id="name" name="name" type="text" class="mt-1 w-full" />
            @error("name")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

        <div class="mt-6">
            <label for="user" class="inline-flex items-center">
                <input
                    id="user"
                    name="user"
                    type="checkbox"
                    x-model="user"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                />
                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                    {{ __("Create a user for this database") }}
                </span>
            </label>
        </div>

        <div x-show="user">
            <div class="mt-6">
                <x-input-label for="db-username" :value="__('Username')" />
                <x-text-input
                    value="{{ old('username') }}"
                    id="db-username"
                    name="username"
                    type="text"
                    class="mt-1 w-full"
                />
                @error("username")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6">
                <x-input-label for="db-password" :value="__('Password')" />
                <x-text-input
                    value="{{ old('password') }}"
                    id="db-password"
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
                    <label for="db-remote" class="inline-flex items-center">
                        <input
                            id="db-remote"
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
                    <x-input-label for="db-host" :value="__('Host')" />
                    <x-text-input value="{{ old('host') }}" id="db-host" name="host" type="text" class="mt-1 w-full" />
                    <x-input-label
                        for="db-host"
                        :value="__('You might also need to open the database port in Firewall')"
                        class="mt-1"
                    />
                    @error("host")
                        <x-input-error class="mt-2" :messages="$message" />
                    @enderror
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end">
            <x-secondary-button type="button" x-on:click="$dispatch('close')">
                {{ __("Cancel") }}
            </x-secondary-button>

            <x-primary-button id="btn-create-database" class="ml-3">
                {{ __("Create") }}
            </x-primary-button>
        </div>
    </form>
</x-modal>
