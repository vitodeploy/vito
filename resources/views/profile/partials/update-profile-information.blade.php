@php
    $user = auth()->user();
@endphp

<x-card>
    <x-slot name="title">
        {{ __("Profile Information") }}
    </x-slot>

    <x-slot name="description">
        {{ __("Update your account's profile information and email address.") }}
    </x-slot>

    <form
        id="update-profile-information"
        hx-post="{{ route("profile.info") }}"
        hx-swap="outerHTML"
        hx-select="#update-profile-information"
        hx-trigger="submit"
        hx-ext="disable-element"
        hx-disable-element="#btn-save-info"
        class="mt-6 space-y-6"
    >
        @csrf
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input
                id="name"
                name="name"
                type="text"
                value="{{ old('name', auth()->user()->name) }}"
                class="mt-1 block w-full"
                required
                autocomplete="name"
            />
            @error("name")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input
                id="email"
                name="email"
                type="email"
                value="{{ old('email', auth()->user()->email) }}"
                class="mt-1 block w-full"
                required
                autocomplete="email"
            />
            @error("email")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

        <div>
            <x-input-label for="timezone" :value="__('Timezone')" />
            <x-select-input id="timezone" name="timezone" class="mt-1 block w-full" required>
                @foreach (timezone_identifiers_list() as $timezone)
                    <option
                        value="{{ $timezone }}"
                        @if(old('timezone', auth()->user()->timezone) == $timezone) selected @endif
                    >
                        {{ $timezone }}
                    </option>
                @endforeach
            </x-select-input>
            @error("timezone")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>
    </form>

    <x-slot name="actions">
        <x-primary-button id="btn-save-info" form="update-profile-information">
            {{ __("Save") }}
        </x-primary-button>
    </x-slot>
</x-card>
