<x-modal
    name="edit-source-control"
    :show="true"
    x-on:modal-edit-source-control-closed.window="window.history.pushState('', '', '{{ route('settings.source-controls') }}');"
>
    <form
        id="edit-source-control-form"
        hx-post="{{ route("settings.source-controls.update", ["sourceControl" => $sourceControl->id]) }}"
        hx-swap="outerHTML"
        hx-select="#edit-source-control-form"
        hx-ext="disable-element"
        hx-disable-element="#btn-edit-source-control"
        class="p-6"
    >
        @csrf

        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __("Edit Source Control") }}
        </h2>

        <div class="mt-6">
            <x-input-label for="name" value="Name" />
            <x-text-input value="{{ $sourceControl->profile }}" id="name" name="name" type="text" class="mt-1 w-full" />
            @error("name")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

        @if ($sourceControl->provider == \App\Enums\SourceControl::GITLAB)
            <div class="mt-6">
                <x-input-label for="url" value="Url (optional)" />
                <x-text-input
                    value="{{ old('url', $sourceControl->url) }}"
                    id="url"
                    name="url"
                    type="text"
                    class="mt-1 w-full"
                    placeholder="e.g. https://gitlab.example.com/"
                />
                <x-input-help>
                    If you run a self-managed gitlab enter the url here, leave empty to use gitlab.com
                </x-input-help>
                @error("url")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>
        @endif

        @if (in_array($sourceControl->provider, [\App\Enums\SourceControl::GITLAB, \App\Enums\SourceControl::GITHUB]))
            <div class="mt-6">
                <x-input-label for="token" value="API Key" />
                <x-text-input
                    value="{{ old('token', $sourceControl->provider()->data()['token']) }}"
                    id="token"
                    name="token"
                    type="text"
                    class="mt-1 w-full"
                />
                @error("token")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>
        @endif

        @if ($sourceControl->provider == \App\Enums\SourceControl::BITBUCKET)
            <div>
                <div class="mt-6">
                    <x-input-label for="username" value="Username" />
                    <x-text-input
                        value="{{ old('username', $sourceControl->provider()->data()['username']) }}"
                        id="username"
                        name="username"
                        type="text"
                        class="mt-1 w-full"
                    />
                    <x-input-help>Your Bitbucket username</x-input-help>
                    @error("username")
                        <x-input-error class="mt-2" :messages="$message" />
                    @enderror
                </div>

                <div class="mt-6">
                    <x-input-label for="password" value="Password" />
                    <x-text-input
                        value="{{ old('password', $sourceControl->provider()->data()['password']) }}"
                        id="password"
                        name="password"
                        type="text"
                        class="mt-1 w-full"
                    />
                    <x-input-help>
                        Create a new
                        <a
                            class="text-primary-500"
                            href="https://bitbucket.org/account/settings/app-passwords/new"
                            target="_blank"
                        >
                            App Password
                        </a>
                        in your Bitbucket account with write and admin access to Workspaces, Projects, Repositories and
                        Webhooks
                    </x-input-help>
                    @error("password")
                        <x-input-error class="mt-2" :messages="$message" />
                    @enderror
                </div>
            </div>
        @endif

        <div class="mt-6">
            <x-checkbox
                id="edit-global"
                name="global"
                :checked="old('global', $sourceControl->project_id === null ? 1 : null)"
                value="1"
            >
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

            <x-primary-button id="btn-edit-source-control" class="ml-3">
                {{ __("Save") }}
            </x-primary-button>
        </div>
    </form>
</x-modal>
