<div>
    <x-primary-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'create-user')">New User</x-primary-button>

    <x-modal name="create-user">
        <form
            id="create-user-form"
            hx-post="{{ route("settings.users.store") }}"
            hx-swap="outerHTML"
            hx-select="#create-user-form"
            hx-ext="disable-element"
            hx-disable-element="#btn-create-user"
            class="p-6"
        >
            @csrf
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Create New User</h2>

            <div class="mt-6">
                <x-input-label for="name" value="Name" />
                <x-text-input value="{{ old('name') }}" id="name" name="name" type="text" class="mt-1 w-full" />
                @error("name")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6">
                <x-input-label for="email" value="Email" />
                <x-text-input value="{{ old('email') }}" id="email" name="email" type="text" class="mt-1 w-full" />
                @error("email")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6">
                <x-input-label for="password" value="Password" />
                <x-text-input id="password" name="password" type="password" class="mt-1 w-full" />
                @error("password")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6">
                <x-input-label for="role" value="Role" />
                <x-select-input id="role" name="role" class="mt-1 w-full">
                    <option
                        value="{{ \App\Enums\UserRole::USER }}"
                        @if(old('role') === \App\Enums\UserRole::USER) selected @endif
                    >
                        User
                    </option>
                    <option
                        value="{{ \App\Enums\UserRole::ADMIN }}"
                        @if(old('role') === \App\Enums\UserRole::ADMIN) selected @endif
                    >
                        Admin
                    </option>
                </x-select-input>
                @error("role")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">Cancel</x-secondary-button>

                <x-primary-button id="btn-create-project" class="ml-3">Create</x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
