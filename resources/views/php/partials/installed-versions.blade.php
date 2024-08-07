<div x-data="{ version: '', uninstallAction: '', installedExtensions: [] }">
    <x-card-header>
        <x-slot name="title">{{ __("Installed PHPs") }}</x-slot>
        <x-slot name="description">
            {{ __("You can see and manage your PHP installations") }}
        </x-slot>
        <x-slot name="aside">
            <div class="flex items-center">
                @include("php.partials.install-new-php")
            </div>
        </x-slot>
    </x-card-header>

    <x-live id="live-phps">
        @if (count($phps) > 0)
            <div class="space-y-3">
                @foreach ($phps as $php)
                    <a class="block">
                        <x-item-card>
                            <div class="flex items-start justify-center">
                                <span class="mr-2">PHP {{ $php->version }}</span>
                                @include("services.partials.status", ["status" => $php->status])
                            </div>
                            <div class="flex items-center">
                                <div class="inline">
                                    <x-dropdown>
                                        <x-slot name="trigger">
                                            <x-secondary-button>
                                                {{ __("Actions") }}
                                                <x-heroicon name="o-chevron-up-down" class="ml-1 h-5 w-5" />
                                            </x-secondary-button>
                                        </x-slot>
                                        <x-slot name="content">
                                            <x-dropdown-link
                                                class="cursor-pointer"
                                                x-on:click="version = '{{ $php->version }}'; installedExtensions = {{ json_encode($php->type_data['extensions'] ?? []) }}; $dispatch('open-modal', 'install-extension')"
                                            >
                                                {{ __("Install Extension") }}
                                            </x-dropdown-link>
                                            @foreach ([\App\Enums\PHPIniType::FPM, \App\Enums\PHPIniType::CLI] as $type)
                                                <x-dropdown-link
                                                    class="cursor-pointer"
                                                    x-on:click="version = '{{ $php->version }}'; $dispatch('open-modal', 'update-php-ini-{{ $type }}');"
                                                    hx-get="{{ route('servers.php.get-ini', ['server' => $server, 'version' => $php->version, 'type' => $type]) }}"
                                                    hx-swap="outerHTML"
                                                    hx-target="#update-php-ini-{{ $type }}-form"
                                                    hx-select="#update-php-ini-{{ $type }}-form"
                                                >
                                                    {{ __("Edit php.ini (:type)", ["type" => $type]) }}
                                                </x-dropdown-link>
                                            @endforeach

                                            <x-dropdown-link
                                                class="cursor-pointer"
                                                href="{{ route('servers.services.restart', ['server' => $server, 'service' => $php]) }}"
                                            >
                                                {{ __("Restart FPM") }}
                                            </x-dropdown-link>
                                            <x-dropdown-link
                                                class="cursor-pointer"
                                                x-on:click="uninstallAction = '{{ route('servers.php.uninstall', ['server' => $server, 'version' => $php->version]) }}'; $dispatch('open-modal', 'uninstall-php')"
                                            >
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
        @else
            <x-simple-card>
                <div class="text-center">
                    {{ __("You don't have any PHP version installed!") }}
                </div>
            </x-simple-card>
        @endif
    </x-live>

    <x-confirmation-modal
        name="uninstall-php"
        :title="__('Uninstall PHP')"
        :description="__('Are you sure you want to uninstall this version?')"
        method="delete"
        x-bind:action="uninstallAction"
    />
    @include("php.partials.update-php-ini", ["type" => \App\Enums\PHPIniType::CLI])
    @include("php.partials.update-php-ini", ["type" => \App\Enums\PHPIniType::FPM])
    @include("php.partials.install-extension")
</div>
