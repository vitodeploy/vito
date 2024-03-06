<nav x-data="{ open: false }" class="h-16 border-b border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
    <div class="mx-auto max-w-full px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <div class="flex"></div>

            <div class="flex items-center">
                <div class="mr-3">
                    @include("layouts.partials.color-scheme")
                </div>

                <x-user-dropdown />
            </div>
        </div>
    </div>
</nav>
