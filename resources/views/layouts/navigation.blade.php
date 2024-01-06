<nav x-data="{ open: false }" class="h-16 border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
    <div class="mx-auto max-w-full px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex">

            </div>

            <div class="flex items-center">
                <div class="mr-2">
                    @include('layouts.partials.color-scheme')
                </div>

                <livewire:user-dropdown />
            </div>
        </div>
    </div>
</nav>
