<x-container x-data="">
    <x-card>
        <x-slot name="title">{{ __("Create new Server") }}</x-slot>
        <x-slot name="description">{{ __("Use this form to create a new server") }}</x-slot>
        <form id="create-server" wire:submit.prevent="submit" class="mt-6 space-y-6">
            <div>
                <x-input-label>{{ __("Select a server provider") }}</x-input-label>
                <div class="grid grid-cols-6 gap-2 mt-1">
                    @foreach(config('core.server_providers') as $p)
                        <x-server-provider-item x-on:click="$wire.provider = '{{ $p }}'" :active="$provider === $p">
                            <div class="flex w-full flex-col items-center justify-center text-center">
                                <img src="{{ asset('static/images/' . $p . '.svg') }}" class="h-7" alt="Server">
                                <span class="md:text-normal mt-2 hidden text-sm md:block">{{ $p }}</span>
                            </div>
                        </x-server-provider-item>
                    @endforeach
                </div>
                @error('provider')
                <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            @if($provider === 'custom')
                @include('livewire.servers.partials.public-key')
            @else
                <div>
                    <x-input-label for="server_provider" value="Provider Profile" />
                    <div class="flex items-center mt-1">
                        <x-select-input wire:model="server_provider" id="server_provider" name="server_provider" class="w-full">
                            <option value="" disabled selected>{{ __("Select") }}</option>
                            @foreach($serverProviders as $sp)
                                <option value="{{ $sp->id }}" @if($sp->id === $server_provider) selected @endif>
                                    {{ $sp->profile }}
                                </option>
                            @endforeach
                        </x-select-input>
                        <x-secondary-button :href="route('server-providers', ['provider' => $provider])" class="flex-none ml-2">{{ __('Connect') }}</x-secondary-button>
                    </div>
                    @error('server_provider')
                    <x-input-error class="mt-2" :messages="$message" />
                    @enderror
                    @if(count($serverProviders) == 0)
                        <x-input-error class="mt-2" :messages="__('You have not connected to any providers!')" />
                    @endif
                </div>
            @endif

            <div>
                <x-input-label for="name" :value="__('Name')" />
                <x-text-input wire:model.defer="name" id="name" name="name" type="text" class="mt-1 block w-full" autocomplete="name" />
                @error('name')
                <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            @if($provider !== 'custom')
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                    <div>
                        <x-input-label for="plan" value="Plan" />
                        <x-select-input wire:model.defer="plan" id="plan" name="plan" class="mt-1 w-full">
                            <option value="" disabled selected>{{ __("Select") }}</option>
                            @foreach(config('serverproviders')[$provider]['plans'] as $value)
                                <option value="{{ $value['value'] }}" @if($value['value'] === $plan) selected @endif>{{ $value['title'] }}</option>
                            @endforeach
                        </x-select-input>
                        @error('plan')
                        <x-input-error class="mt-2" :messages="$message" />
                        @enderror
                    </div>
                    <div>
                        <x-input-label for="region" value="Region" />
                        <x-select-input wire:model.defer="region" id="region" name="region" class="mt-1 w-full">
                            <option value="" disabled selected>{{ __("Select") }}</option>
                            @foreach(config('serverproviders')[$provider]['regions'] as $key => $value)
                                <option value="{{ $value['value'] }}" @if($value['value'] === $plan) selected @endif>{{ $value['title'] }}</option>
                            @endforeach
                        </x-select-input>
                        @error('region')
                        <x-input-error class="mt-2" :messages="$message" />
                        @enderror
                    </div>
                </div>
            @endif

            @if($provider === 'custom')
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <x-input-label for="ip" :value="__('SSH IP Address')" />
                        <x-text-input wire:model.defer="ip" id="ip" name="ip" type="text" class="mt-1 block w-full" autocomplete="ip" />
                        @error('ip')
                        <x-input-error class="mt-2" :messages="$message" />
                        @enderror
                    </div>
                    <div>
                        <x-input-label for="port" :value="__('SSH Port')" />
                        <x-text-input wire:model.defer="port" id="port" name="port" type="text" class="mt-1 block w-full" autocomplete="port" />
                        @error('port')
                        <x-input-error class="mt-2" :messages="$message" />
                        @enderror
                    </div>
                </div>
            @endif

            <div>
                <x-input-label for="os" value="Operating System" />
                <x-select-input wire:model.defer="os" id="os" name="os" class="mt-1 w-full">
                    @foreach(config('core.operating_systems') as $operatingSystem)
                        <option value="{{ $operatingSystem }}" @if($operatingSystem === $os) selected @endif>
                            {{ str($operatingSystem)->replace('_', ' ')->ucfirst() }} LTS
                        </option>
                    @endforeach
                </x-select-input>
                @error('os')
                <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div>
                <x-input-label for="type" value="Server Type" />
                <x-select-input wire:model.defer="type" id="type" name="type" class="mt-1 w-full">
                    @foreach(config('core.server_types') as $serverType)
                        <option value="{{ $serverType }}" @if($type === $serverType) selected @endif>
                            {{ $serverType }}
                        </option>
                    @endforeach
                </x-select-input>
                @error('type')
                <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
                <div>
                    <x-input-label for="webserver" value="Webserver" />
                    <x-select-input wire:model.defer="webserver" id="webserver" name="webserver" class="mt-1 w-full">
                        @foreach(config('core.webservers') as $ws)
                            <option value="{{ $ws }}" @if($ws === $webserver) selected @endif>{{ $ws }}</option>
                        @endforeach
                    </x-select-input>
                    @error('webserver')
                    <x-input-error class="mt-2" :messages="$message" />
                    @enderror
                </div>
                <div>
                    <x-input-label for="database" value="Database" />
                    <x-select-input wire:model.defer="database" id="database" name="database" class="mt-1 w-full">
                        @foreach(config('core.databases') as $db)
                            <option value="{{ $db }}" @if($db === $database) selected @endif>{{ $db }}</option>
                        @endforeach
                    </x-select-input>
                    @error('database')
                    <x-input-error class="mt-2" :messages="$message" />
                    @enderror
                </div>
                <div>
                    <x-input-label for="php" value="PHP" />
                    <x-select-input wire:model.defer="php" id="php" name="php" class="mt-1 w-full">
                        @foreach(config('core.php_versions') as $p)
                            <option value="{{ $p }}" @if($p === $php) selected @endif>{{ $p }}</option>
                        @endforeach
                    </x-select-input>
                    @error('php')
                    <x-input-error class="mt-2" :messages="$message" />
                    @enderror
                </div>
            </div>
        </form>
        <x-slot name="actions">
            <x-primary-button form="create-server" wire:loading.attr="disabled">{{ __('Create') }}</x-primary-button>
        </x-slot>
    </x-card>
</x-container>
