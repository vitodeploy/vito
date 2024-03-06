<div>
    <x-card-header>
        <x-slot name="title">{{ __("Application") }}</x-slot>
        <x-slot name="description">
            {{ __("Here you can manage your application") }}
        </x-slot>
        <x-slot name="aside">
            <div class="flex items-center">
                <div class="mr-2">
                    @include("application.deploy")
                </div>
                @if ($site->source_control_id)
                    <div class="mr-2">
                        @include("application.auto-deployment")
                    </div>
                @endif

                <x-dropdown>
                    <x-slot name="trigger">
                        <x-secondary-button>
                            {{ __("Manage") }}
                        </x-secondary-button>
                    </x-slot>
                    <x-slot name="content">
                        @if ($site->source_control_id)
                            <x-dropdown-link
                                class="cursor-pointer"
                                x-on:click="$dispatch('open-modal', 'change-branch')"
                            >
                                {{ __("Branch") }}
                            </x-dropdown-link>
                        @endif

                        <x-dropdown-link
                            class="cursor-pointer"
                            x-on:click="$dispatch('open-modal', 'deployment-script')"
                        >
                            {{ __("Deployment Script") }}
                        </x-dropdown-link>
                        <x-dropdown-link class="cursor-pointer" x-on:click="$dispatch('open-modal', 'update-env')">
                            {{ __(".env") }}
                        </x-dropdown-link>
                    </x-slot>
                </x-dropdown>
                @if ($site->source_control_id)
                    @include("application.change-branch")
                @endif

                @include("application.deployment-script")
                @include("application.env")
            </div>
        </x-slot>
    </x-card-header>

    @include("application.deployments-list")
</div>
