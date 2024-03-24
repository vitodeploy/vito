<x-dropdown>
    <x-slot name="trigger">
        <x-primary-button hx-disable>
            {{ __("Install PHP") }}
            <x-heroicon name="o-chevron-up-down" class="ml-1 h-5 w-5" />
        </x-primary-button>
    </x-slot>

    <x-slot name="content">
        @foreach (config("core.php_versions") as $php)
            @if (! $phps->whereIn("version", $php)->first() && $php !== "none")
                <x-dropdown-link
                    class="cursor-pointer"
                    hx-post="{{ route('servers.php.install', ['server' => $server, 'version' => $php]) }}"
                    hx-swap="none"
                >
                    PHP {{ $php }}
                </x-dropdown-link>
            @endif
        @endforeach
    </x-slot>
</x-dropdown>
