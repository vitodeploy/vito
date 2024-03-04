<div>
    <x-card-header>
        <x-slot name="title">{{ __("Application") }}</x-slot>
        <x-slot name="description">
            {{ __("Here you can manage your application") }}
        </x-slot>
        <x-slot name="aside">
            <div class="flex items-center">
                <div class="mr-2">
                    <livewire:application.deploy :site="$site" />
                </div>
                @if ($site->source_control_id)
                    <div class="mr-2">
                        <livewire:application.auto-deployment :site="$site" />
                    </div>
                @endif

                <x-dropdown>
                    <x-slot name="trigger">
                        <x-secondary-button>
                            {{ __("Manage") }}
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                                class="ml-1 h-5 w-5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9"
                                />
                            </svg>
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
                    <livewire:application.change-branch :site="$site" />
                @endif

                <livewire:application.deployment-script :site="$site" />
                <livewire:application.env :site="$site" />
            </div>
        </x-slot>
    </x-card-header>

    <livewire:application.deployments-list :site="$site" />
</div>
