<div>
    <x-primary-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'create-queue')">
        {{ __("Create Queue") }}
    </x-primary-button>

    <x-modal name="create-queue">
        <form
            id="create-queue-form"
            hx-post="{{ route("servers.sites.queues.store", ["server" => $server, "site" => $site]) }}"
            hx-swap="outerHTML"
            hx-select="#create-queue-form"
            class="p-6"
        >
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __("Create Queue") }}
            </h2>

            <div class="mt-6">
                <x-input-label for="command" :value="__('Command')" />
                <x-text-input
                    value="{{ old('command') }}"
                    id="command"
                    name="command"
                    type="text"
                    class="mt-1 w-full"
                />
                @error("command")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6">
                <x-input-label for="user" :value="__('User')" />
                <x-select-input id="user" name="user" class="mt-1 w-full">
                    <option value="" selected disabled>
                        {{ __("Select") }}
                    </option>
                    <option value="root" @if(old('user') == 'root') selected @endif>root</option>
                    <option
                        value="{{ $server->getSshUser() }}"
                        @if(old('user') == $server->getSshUser()) selected @endif
                    >
                        {{ $server->getSshUser() }}
                    </option>
                </x-select-input>
                @error("user")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6">
                <x-input-label for="auto_start" :value="__('Auto Start')" />
                <x-select-input id="auto_start" name="auto_start" class="mt-1 w-full">
                    <option value="" selected disabled>
                        {{ __("Select") }}
                    </option>
                    <option value="1" @if(old('auto_start')) selected @endif>
                        {{ __("Yes") }}
                    </option>
                    <option value="0" @if(!old('auto_start')) selected @endif>
                        {{ __("No") }}
                    </option>
                </x-select-input>
                @error("auto_start")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6">
                <x-input-label for="auto_restart" :value="__('Auto Restart')" />
                <x-select-input id="auto_restart" name="auto_restart" class="mt-1 w-full">
                    <option value="" selected disabled>
                        {{ __("Select") }}
                    </option>
                    <option value="1" @if(old('auto_restart')) selected @endif>
                        {{ __("Yes") }}
                    </option>
                    <option value="0" @if(!old('auto_restart')) selected @endif>
                        {{ __("No") }}
                    </option>
                </x-select-input>
                @error("auto_restart")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6">
                <x-input-label for="numprocs" :value="__('Numprocs')" />
                <x-text-input
                    value="{{ old('numprocs') }}"
                    id="numprocs"
                    name="numprocs"
                    type="text"
                    class="mt-1 w-full"
                    placeholder="2"
                />
                @error("numprocs")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
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
