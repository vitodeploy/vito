<div>
    <x-primary-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'connect-provider')">
        {{ __("Connect") }}
    </x-primary-button>

    <x-modal name="connect-provider">
        @php
            $oldProvider = old("provider", request()->input("provider") ?? "");
        @endphp

        <form
            id="connect-storage-provider-form"
            hx-post="{{ route("storage-providers.connect") }}"
            hx-swap="outerHTML"
            hx-select="#connect-storage-provider-form"
            hx-ext="disable-element"
            hx-disable-element="#btn-connect-storage-provider"
            class="p-6"
            x-data="{ provider: '{{ $oldProvider }}' }"
        >
            @csrf
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __("Connect to a Storage Provider") }}
            </h2>

            <div class="mt-6">
                <x-input-label for="provider" value="Provider" />
                <x-select-input x-model="provider" id="provider" name="provider" class="mt-1 w-full">
                    <option value="" selected disabled>
                        {{ __("Select") }}
                    </option>
                    @foreach (config("core.storage_providers") as $p)
                        @if ($p !== "custom")
                            <option value="{{ $p }}" @if($oldProvider === $p) selected @endif>
                                {{ $p }}
                            </option>
                        @endif
                    @endforeach
                </x-select-input>
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

            <div x-show="provider === 'dropbox'" class="mt-6">
                <x-input-label for="token" value="API Key" />
                <x-text-input value="{{ old('token') }}" id="token" name="token" type="text" class="mt-1 w-full" />
                @error("token")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div x-show="provider === 'ftp'" class="mt-6">
                <div class="grid grid-cols-2 gap-2">
                    <div class="mt-6">
                        <x-input-label for="host" value="Host" />
                        <x-text-input value="{{ old('host') }}" id="host" name="host" type="text" class="mt-1 w-full" />
                        @error("host")
                            <x-input-error class="mt-2" :messages="$message" />
                        @enderror
                    </div>
                    <div class="mt-6">
                        <x-input-label for="port" value="Port" />
                        <x-text-input value="{{ old('port') }}" id="port" name="port" type="text" class="mt-1 w-full" />
                        @error("port")
                            <x-input-error class="mt-2" :messages="$message" />
                        @enderror
                    </div>
                </div>
                <div class="mt-6">
                    <x-input-label for="path" value="Path" />
                    <x-text-input value="{{ old('path') }}" id="path" name="path" type="text" class="mt-1 w-full" />
                    @error("path")
                        <x-input-error class="mt-2" :messages="$message" />
                    @enderror
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div class="mt-6">
                        <x-input-label for="username" value="Username" />
                        <x-text-input
                            value="{{ old('username') }}"
                            id="username"
                            name="username"
                            type="text"
                            class="mt-1 w-full"
                        />
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
                        @error("password")
                            <x-input-error class="mt-2" :messages="$message" />
                        @enderror
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div class="mt-6">
                        <x-input-label for="ssl" :value="__('SSL')" />
                        <x-select-input id="ssl" name="ssl" class="mt-1 w-full">
                            <option value="1" @if(old('ssl')) selected @endif>
                                {{ __("Yes") }}
                            </option>
                            <option value="0" @if(!old('ssl')) selected @endif>
                                {{ __("No") }}
                            </option>
                        </x-select-input>
                        @error("ssl")
                            <x-input-error class="mt-2" :messages="$message" />
                        @enderror
                    </div>
                    <div class="mt-6">
                        <x-input-label for="passive" :value="__('Passive')" />
                        <x-select-input id="passive" name="passive" class="mt-1 w-full">
                            <option value="1" @if(old('passive')) selected @endif>
                                {{ __("Yes") }}
                            </option>
                            <option value="0" @if(!old('passive')) selected @endif>
                                {{ __("No") }}
                            </option>
                        </x-select-input>
                        @error("passive")
                            <x-input-error class="mt-2" :messages="$message" />
                        @enderror
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    {{ __("Cancel") }}
                </x-secondary-button>

                <x-primary-button id="btn-connect-storage-provider" class="ml-3">
                    {{ __("Connect") }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
