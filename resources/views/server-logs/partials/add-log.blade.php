<div x-data="">
    <x-card-header>
        <x-slot name="title">{{ __("Manage your remote logs") }}</x-slot>
        <x-slot name="description">
            {{ __("Here you can add new logs") }}
        </x-slot>
        <x-slot name="aside">
            <div class="flex flex-col items-end lg:flex-row lg:items-center">
                <x-primary-button class="cursor-pointer" x-on:click="$dispatch('open-modal', 'add-log')">
                    {{ __("Add Remote Log") }}
                </x-primary-button>

                <x-modal name="add-log">
                    <form
                        id="add-log-form"
                        hx-post="{{ route("servers.logs.remote.store", ["server" => $server]) }}"
                        hx-select="#add-log-form"
                        hx-swap="outerHTML"
                        class="p-6"
                    >
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            {{ __("Add Remote Log") }}
                        </h2>

                        <div class="mt-6">
                            <x-input-label for="path" :value="__('Please introduce the full path to the log file.')" />
                            <x-text-input
                                list="sites"
                                value="{{ old('path') }}"
                                id="path"
                                name="path"
                                type="text"
                                class="mt-1 w-full"
                            />
                            <datalist id="sites">
                                @foreach ($server->sites as $site)
                                    <option>{{ str($site->path)->append("/") }}</option>
                                @endforeach
                            </datalist>
                            @error("path")
                                <x-input-error class="mt-2" :messages="$message" />
                            @enderror
                        </div>

                        <div class="mt-6 flex items-center justify-end">
                            <x-secondary-button type="button" x-on:click="$dispatch('close')">
                                {{ __("Cancel") }}
                            </x-secondary-button>

                            <x-primary-button class="ml-3" hx-disable>
                                {{ __("Save") }}
                            </x-primary-button>
                        </div>
                    </form>
                </x-modal>
            </div>
        </x-slot>
    </x-card-header>
</div>
