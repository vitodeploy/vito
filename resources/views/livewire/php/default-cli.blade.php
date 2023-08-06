<div>
    <x-card-header>
        <x-slot name="title">{{ __("Default PHP Cli") }}</x-slot>
        <x-slot name="description">{{ __("You can see and manage your PHP installations") }}</x-slot>
    </x-card-header>

    <a class="block">
        <x-item-card>
            <div class="flex items-start justify-center">
                <span class="mr-2">PHP {{ $defaultPHP->version }}</span>
                @include('livewire.services.partials.status', ['status' => $defaultPHP->status])
            </div>
            <div class="flex items-center">
                <div class="inline">
                    <x-dropdown>
                        <x-slot name="trigger">
                            <x-secondary-button>
                                {{ __("Change") }}
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="ml-1 h-5 w-5"><path fill-rule="evenodd" d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z" clip-rule="evenodd"></path></svg>
                            </x-secondary-button>
                        </x-slot>
                        <x-slot name="content">
                            @foreach($phps as $php)
                                @if($php->version != $defaultPHP->version)
                                    <x-dropdown-link class="cursor-pointer" wire:click="change('{{ $php->version }}')">
                                        PHP {{ $php->version }}
                                    </x-dropdown-link>
                                @endif
                            @endforeach
                            @if(count($phps) == 1)
                                <x-dropdown-link>
                                    {{ __("No other versions") }}
                                </x-dropdown-link>
                            @endif
                        </x-slot>
                    </x-dropdown>
                </div>
            </div>
        </x-item-card>
    </a>
</div>
