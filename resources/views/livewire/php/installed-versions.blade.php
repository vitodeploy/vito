<div>
    <x-card-header>
        <x-slot name="title">{{ __("Installed PHPs") }}</x-slot>
        <x-slot name="description">{{ __("You can see and manage your PHP installations") }}</x-slot>
        <x-slot name="aside">
            @include('livewire.php.partials.install-new-php')
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
                                        </x-secondary-button>
                                    </x-slot>
                                    <x-slot name="content">
                                        {{--<x-dropdown-link class="cursor-pointer">--}}
                                        {{--    {{ __("Install Extension") }}--}}
                                        {{--</x-dropdown-link>--}}
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
    @else
        <x-simple-card>
            <div class="text-center">
                {{ __("You don't have any PHP version installed!") }}
            </div>
        </x-simple-card>
    @endif
</div>
