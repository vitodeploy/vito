<div>
    <x-card-header>
        <x-slot name="title">{{ __("Default PHP Cli") }}</x-slot>
        <x-slot name="description">
            {{ __("You can see and manage your PHP installations") }}
        </x-slot>
    </x-card-header>

    <x-live id="php-default-cli">
        <a class="block">
            <x-item-card>
                <div class="flex items-start justify-center">
                    <span class="mr-2">PHP {{ $defaultPHP->version }}</span>
                    @include("services.partials.status", ["status" => $defaultPHP->status])
                </div>
                <div class="flex items-center">
                    <div class="inline">
                        <x-dropdown>
                            <x-slot name="trigger">
                                <x-secondary-button>
                                    {{ __("Change") }}
                                    <x-heroicon name="o-chevron-up-down" class="ml-1 h-5 w-5" />
                                </x-secondary-button>
                            </x-slot>
                            <x-slot name="content">
                                @foreach ($phps as $php)
                                    @if ($php->version != $defaultPHP->version)
                                        <x-dropdown-link
                                            id="php-{{ $php->id }}-default-cli"
                                            class="cursor-pointer"
                                            hx-post="{{ route('servers.php.default-cli', ['server' => $server, 'version' => $php->version]) }}"
                                            hx-swap="outerHTML"
                                            hx-select="#php-{{ $php->id }}-default-cli"
                                        >
                                            PHP {{ $php->version }}
                                        </x-dropdown-link>
                                    @endif
                                @endforeach

                                @if (count($phps) == 1)
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
    </x-live>
</div>
