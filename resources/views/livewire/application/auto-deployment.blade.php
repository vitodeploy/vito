<div>
    @if ($site->deploymentScript?->content)
        <x-dropdown>
            <x-slot name="trigger">
                <x-secondary-button>
                    {{ __("Auto Deployment") }}
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
                <x-dropdown-link class="cursor-pointer" wire:click="enable">
                    {{ __("Enable") }}
                    @if ($site->auto_deployment)
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            class="ml-1 h-5 w-5 text-green-600"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                    @endif
                </x-dropdown-link>
                <x-dropdown-link class="cursor-pointer" wire:click="disable">
                    {{ __("Disable") }}
                    @if (! $site->auto_deployment)
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            class="ml-1 h-5 w-5 text-green-600"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                    @endif
                </x-dropdown-link>
            </x-slot>
        </x-dropdown>
    @endif
</div>
