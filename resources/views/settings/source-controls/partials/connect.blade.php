<div>
    <x-primary-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'connect-source-control')">
        {{ __("Connect") }}
    </x-primary-button>

    <x-modal name="connect-source-control" :show="request()->has('provider')">
        @php
            $oldProvider = old("provider", request()->input("provider") ?? "");
        @endphp

        <form
            id="connect-source-control-form"
            hx-post="{{ route("settings.source-controls.connect") }}"
            hx-swap="outerHTML"
            hx-select="#connect-source-control-form"
            hx-ext="disable-element"
            hx-disable-element="#btn-connect-source-control"
            class="p-6"
            x-data="{ provider: '{{ $oldProvider }}' }"
        >
            @csrf

            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __("Connect to a Source Control") }}
            </h2>

            <div class="mt-6">
                <x-input-label for="provider" value="Provider" />
                <x-select-input x-model="provider" id="provider" name="provider" class="mt-1 w-full">
                    <option value="" selected disabled>
                        {{ __("Select") }}
                    </option>
                    @foreach (config("core.source_control_providers") as $p)
                        @if ($p !== "custom")
                            <option value="{{ $p }}" @if($oldProvider === $p) selected @endif>
                                {{ $p }}
                            </option>
                        @endif
                    @endforeach
                </x-select-input>
                <x-input-help>
                    Check
                    <a
                        href="https://vitodeploy.com/settings/source-controls.html"
                        class="text-primary-500"
                        target="_blank"
                    >
                        here
                    </a>
                    to see what permissions you need
                </x-input-help>
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

            <div x-show="provider === 'gitlab'" class="mt-6">
                <x-input-label for="url" value="Url (optional)" />
                <x-text-input
                    value="{{ old('url') }}"
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

            <div x-show="['gitlab', 'github'].includes(provider)" class="mt-6">
                <x-input-label for="token" value="API Key" />
                <x-text-input value="{{ old('token') }}" id="token" name="token" type="text" class="mt-1 w-full" />
                @error("token")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div x-show="provider === 'bitbucket'">
                <div class="mt-6">
                    <x-input-label for="username" value="Username" />
                    <x-text-input
                        value="{{ old('username') }}"
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
                        value="{{ old('password') }}"
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

                <x-primary-button id="btn-connect-source-control" class="ml-3">
                    {{ __("Connect") }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
