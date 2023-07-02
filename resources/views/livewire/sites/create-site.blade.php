<div x-data="">
    <x-card>
        <x-slot name="title">{{ __("Create new site") }}</x-slot>
        <x-slot name="description">{{ __("Use this form to create a new site") }}</x-slot>
        <form id="create-site" wire:submit.prevent="create" class="space-y-6">
            <div>
                <x-input-label>{{ __("Select site type") }}</x-input-label>
                <div class="grid grid-cols-6 gap-2 mt-1">
                    @foreach(config('core.site_types') as $t)
                        <x-site-type-item x-on:click="$wire.type = '{{ $t }}'" :active="$type === $t">
                            <div class="flex w-full flex-col items-center justify-center text-center">
                                <img src="{{ asset('static/images/' . $t . '.svg') }}" class="h-7" alt="Server">
                                <span class="md:text-normal mt-2 hidden text-sm md:block">{{ $t }}</span>
                            </div>
                        </x-site-type-item>
                    @endforeach
                </div>
                @error('type')
                <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div>
                <x-input-label for="domain" :value="__('Domain')" />
                <x-text-input wire:model.defer="domain" id="domain" name="domain" type="text" class="mt-1 block w-full" autocomplete="domain" placeholder="example.com" />
                @error('domain')
                <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div>
                <x-input-label for="alias" :value="__('Alias')" />
                <x-text-input wire:model.defer="alias" id="alias" name="alias" type="text" class="mt-1 block w-full" autocomplete="alias" placeholder="www.example.com" />
                @error('alias')
                <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div>
                <x-input-label for="php_version" :value="__('PHP Version')" />
                <x-select-input wire:model.defer="php_version" id="php_version" name="php_version" class="mt-1 w-full">
                    <option value="" selected disabled>{{ __("Select") }}</option>
                    @foreach($server->installedPHPVersions() as $version)
                        <option value="{{ $version }}" @if($version === $php_version) selected @endif>
                            PHP {{ $version }}
                        </option>
                    @endforeach
                </x-select-input>
                @error('php_version')
                <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div>
                <x-input-label for="web_directory" :value="__('Web Directory')" />
                <x-text-input wire:model.defer="web_directory" id="web_directory" name="web_directory" type="text" class="mt-1 block w-full" autocomplete="web_directory" />
                @error('web_directory')
                <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div>
                <x-input-label for="source_control" :value="__('Source Control')" />
                <x-select-input wire:model="source_control" id="source_control" name="source_control" class="mt-1 w-full">
                    <option value="" selected disabled>{{ __("Select") }}</option>
                    @foreach($sourceControls as $sourceControl)
                        <option value="{{ $sourceControl->provider }}" @if($sourceControl->provider === $source_control) selected @endif>
                            {{ ucfirst($sourceControl->provider) }}
                        </option>
                    @endforeach
                </x-select-input>
                @error('source_control')
                <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div>
                <x-input-label for="repository" :value="__('Repository')" />
                <x-text-input wire:model.defer="repository" id="repository" name="repository" type="text" class="mt-1 block w-full" autocomplete="repository" placeholder="organization/repository" />
                @error('repository')
                <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div>
                <x-input-label for="branch" :value="__('Branch')" />
                <x-text-input wire:model.defer="branch" id="branch" name="branch" type="text" class="mt-1 block w-full" autocomplete="branch" placeholder="main" />
                @error('branch')
                <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6">
                <label for="composer" class="inline-flex items-center">
                    <input id="composer" wire:model.defer="composer" type="checkbox" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" name="composer">
                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Run `composer install --no-dev`') }}</span>
                </label>
            </div>
        </form>
        <x-slot name="actions">
            <x-primary-button form="create-site" wire:loading.attr="disabled">{{ __('Create') }}</x-primary-button>
        </x-slot>
    </x-card>
</div>
