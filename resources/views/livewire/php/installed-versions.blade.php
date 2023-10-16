<div>
    <x-card-header>
        <x-slot name="title">{{ __("Installed PHPs") }}</x-slot>
        <x-slot name="description">{{ __("You can see and manage your PHP installations") }}</x-slot>
        <x-slot name="aside">
            <div class="flex items-center">
                @include('livewire.php.partials.install-new-php')
            </div>
        </x-slot>
    </x-card-header>

    @if(count($phps) > 0)
        <div class="space-y-3">
            @foreach($phps as $php)
                <a class="block">
                    <x-item-card>
                        <div class="flex items-start justify-center">
                            <span class="mr-2">PHP {{ $php->version }}</span>
                            @include('livewire.services.partials.status', ['status' => $php->status])
                        </div>
                        <div class="flex items-center">
                            <div class="inline">
                                <x-dropdown>
                                    <x-slot name="trigger">
                                        <x-secondary-button>
                                            {{ __("Actions") }}
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="ml-1 h-5 w-5"><path fill-rule="evenodd" d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z" clip-rule="evenodd"></path></svg>
                                        </x-secondary-button>
                                    </x-slot>
                                    <x-slot name="content">
                                        <x-dropdown-link class="cursor-pointer" x-on:click="$wire.extensionId = {{ $php->id }}; $dispatch('open-modal', 'install-extension')">
                                            {{ __("Install Extension") }}
                                        </x-dropdown-link>
                                        <x-dropdown-link class="cursor-pointer" x-on:click="$dispatch('open-modal', 'update-php-ini')" wire:click="loadIni({{ $php->id }})">
                                            {{ __("Edit php.ini") }}
                                        </x-dropdown-link>
                                        <x-dropdown-link class="cursor-pointer" wire:click="restart({{ $php->id }})">
                                            {{ __("Restart FPM") }}
                                        </x-dropdown-link>
                                        <x-dropdown-link class="cursor-pointer" x-on:click="$wire.uninstallId = {{ $php->id }}; $dispatch('open-modal', 'uninstall-php')">
                                            <span class="text-red-600">
                                                {{ __("Uninstall") }}
                                            </span>
                                        </x-dropdown-link>
                                    </x-slot>
                                </x-dropdown>
                            </div>
                        </div>
                    </x-item-card>
                </a>
            @endforeach
        </div>
        @include('livewire.php.partials.uninstall-php')
        @include('livewire.php.partials.update-php-ini')
        @include('livewire.php.partials.install-extension')
    @else
        <x-simple-card>
            <div class="text-center">
                {{ __("You don't have any PHP version installed!") }}
            </div>
        </x-simple-card>
    @endif
</div>
