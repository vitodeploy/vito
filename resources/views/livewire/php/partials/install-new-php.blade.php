<x-dropdown>
    <x-slot name="trigger">
        <x-primary-button>
            {{ __("Install") }}
        </x-primary-button>
    </x-slot>

    <x-slot name="content">
        @foreach(config('core.php_versions') as $php)
            @if(!$phps->whereIn('version', $php)->first() && $php !== 'none')
                <x-dropdown-link class="cursor-pointer" wire:click="install('{{ $php }}')">
                    PHP {{ $php }}
                </x-dropdown-link>
            @endif
        @endforeach
    </x-slot>
</x-dropdown>
