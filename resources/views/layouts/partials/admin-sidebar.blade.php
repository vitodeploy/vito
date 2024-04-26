<ul class="space-y-2 font-medium">
    <li>
        <x-sidebar-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
            <x-heroicon name="o-home" class="h-6 w-6" />
            <span class="ml-2">Dashboard</span>
        </x-sidebar-link>
    </li>
    <li>
        <x-sidebar-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
            <x-heroicon name="o-user-group" class="h-6 w-6" />
            <span class="ml-2">Users</span>
        </x-sidebar-link>
    </li>
    <li>
        <x-sidebar-link href="/">
            <x-heroicon name="o-arrow-left-circle" class="h-6 w-6" />
            <span class="ml-2">User Area</span>
        </x-sidebar-link>
    </li>
</ul>
