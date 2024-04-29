<x-card>
    <x-slot name="title">User Info</x-slot>

    <x-slot name="description">You can update user's info here</x-slot>

    <form
        id="update-user-info"
        hx-post="{{ route("settings.users.update", ["user" => $user]) }}"
        hx-swap="outerHTML"
        hx-select="#update-user-info"
        hx-trigger="submit"
        hx-ext="disable-element"
        hx-disable-element="#btn-save-info"
        class="mt-6 space-y-6"
    >
        @csrf
        <div>
            <x-input-label for="name" value="Name" />
            <x-text-input
                id="name"
                name="name"
                type="text"
                value="{{ old('name', $user->name) }}"
                class="mt-1 block w-full"
                required
                autocomplete="name"
            />
            @error("name")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input
                id="email"
                name="email"
                type="email"
                value="{{ old('email', $user->email) }}"
                class="mt-1 block w-full"
                required
                autocomplete="email"
            />
            @error("email")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

        <div>
            <x-input-label for="timezone" value="Timezone" />
            <x-select-input id="timezone" name="timezone" class="mt-1 block w-full" required>
                @foreach (timezone_identifiers_list() as $timezone)
                    <option
                        value="{{ $timezone }}"
                        @if(old('timezone', $user->timezone) == $timezone) selected @endif
                    >
                        {{ $timezone }}
                    </option>
                @endforeach
            </x-select-input>
            @error("timezone")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

        <div>
            <x-input-label for="role" value="Role" />
            <x-select-input id="role" name="role" class="mt-1 w-full">
                <option
                    value="{{ \App\Enums\UserRole::USER }}"
                    @if(old('role', $user->role) === \App\Enums\UserRole::USER) selected @endif
                >
                    User
                </option>
                <option
                    value="{{ \App\Enums\UserRole::ADMIN }}"
                    @if(old('role', $user->role) === \App\Enums\UserRole::ADMIN) selected @endif
                >
                    Admin
                </option>
            </x-select-input>
            @error("role")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

        <div>
            <x-input-label for="password" value="New Password" />
            <x-text-input id="password" name="password" type="password" class="mt-1 w-full" />
            @error("password")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>
    </form>

    <x-slot name="actions">
        <x-primary-button id="btn-save-info" form="update-user-info">Save</x-primary-button>
    </x-slot>
</x-card>
