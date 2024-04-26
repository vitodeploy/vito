<aside
    id="logo-sidebar"
    class="fixed left-0 top-0 z-40 h-screen w-64 -translate-x-full border-r border-gray-200 bg-white pt-20 transition-transform dark:border-gray-700 dark:bg-gray-800 sm:translate-x-0"
    aria-label="Sidebar"
>
    <div class="h-full overflow-y-auto bg-white px-3 pb-4 dark:bg-gray-800">
        @if (request()->is("admin*"))
            @include("layouts.partials.admin-sidebar")
        @else
            @include("layouts.partials.user-sidebar")
        @endif
    </div>
</aside>
