<nav
    class="fixed top-0 z-50 flex h-[64px] w-full items-center border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800"
>
    <div class="w-full">
        <div class="flex items-center justify-between">
            <div class="flex flex-none items-center justify-start">
                <div
                    class="flex items-center justify-start border-r border-gray-200 px-3 py-3 dark:border-gray-700 md:w-64"
                >
                    <button
                        data-drawer-target="logo-sidebar"
                        data-drawer-toggle="logo-sidebar"
                        aria-controls="logo-sidebar"
                        type="button"
                        class="inline-flex items-center rounded-md p-2 text-sm text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600 sm:hidden"
                    >
                        <span class="sr-only">Open sidebar</span>
                        <x-heroicon name="o-bars-3-center-left" class="h-6 w-6" />
                    </button>
                    <a href="/" class="ms-2 flex flex-none md:me-24">
                        <div class="relative flex items-center justify-start text-3xl font-extrabold">
                            <x-application-logo class="h-9 w-9 rounded-md" />
                            <span class="ml-1 hidden md:block">Deploy</span>
                            <span
                                class="absolute bottom-0 left-0 right-0 rounded-b-md bg-gray-700/60 text-center text-xs text-white md:relative md:ml-1 md:block md:bg-inherit md:text-inherit"
                            >
                                {{ vito_version() }}
                            </span>
                        </div>
                    </a>
                </div>
                <div class="ml-5 flex cursor-pointer items-center" x-data="">
                    <div class="mr-2">
                        @include("layouts.partials.project-select")
                    </div>

                    <div
                        class="flex h-10 w-full items-center rounded-md border border-gray-200 bg-gray-100 px-4 py-2 text-sm text-gray-900 focus:ring-4 focus:ring-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:focus:ring-gray-600"
                        @click="$dispatch('open-search')"
                    >
                        <x-heroicon name="o-magnifying-glass" class="h-4 w-4" />
                        <span class="ml-2 hidden lg:block">Press / to Search</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center px-3 py-3">
                <div class="mr-3">
                    @include("layouts.partials.color-scheme")
                </div>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button
                            class="flex rounded-full p-1 text-sm focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-600"
                        >
                            <x-heroicon name="o-cog-6-tooth" class="h-8 w-8 rounded-full" />
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <div class="px-4 py-3" role="none">
                            <p class="text-sm text-gray-900 dark:text-white" role="none">
                                {{ auth()->user()->name }}
                            </p>
                            <p class="truncate text-sm font-medium text-gray-900 dark:text-gray-300" role="none">
                                {{ auth()->user()->email }}
                            </p>
                        </div>

                        <x-dropdown-link :href="route('profile')">
                            {{ __("Profile") }}
                        </x-dropdown-link>

                        @if (auth()->user()->isAdmin())
                            <x-dropdown-link :href="route('settings.projects')">
                                {{ __("Projects") }}
                            </x-dropdown-link>
                        @endif

                        <!-- Authentication -->
                        <form method="POST" action="{{ route("logout") }}">
                            @csrf
                            <x-dropdown-link
                                :href="route('logout')"
                                onclick="event.preventDefault();this.closest('form').submit();"
                            >
                                {{ __("Log Out") }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>
        </div>
    </div>
</nav>
