<x-filament::dropdown placement="bottom-start" :size="true" :teleport="true" class="pointer-choices -mx-2">
    <x-slot name="trigger">
        <button
            @if (filament()->isSidebarCollapsibleOnDesktop())
                x-data="{ tooltip: false }"
                x-effect="
                    tooltip = $store.sidebar.isOpen
                        ? false
                        : {
                              content: @js($currentProject->name),
                              placement: document.dir === 'rtl' ? 'left' : 'right',
                              theme: $store.theme,
                          }
                "
                x-tooltip.html="tooltip"
            @endif
            type="button"
            class="fi-tenant-menu-trigger group flex w-full items-center justify-center gap-x-3 rounded-lg p-2 text-sm font-medium outline-none transition duration-75 hover:bg-gray-100 focus-visible:bg-gray-100 dark:hover:bg-white/5 dark:focus-visible:bg-white/5"
        >
            <div
                class="bg-primary-700-gradient text-md flex h-8 w-8 items-center justify-center rounded-lg capitalize text-white"
            >
                {{ $currentProject->name[0] }}
            </div>

            <span
                @if (filament()->isSidebarCollapsibleOnDesktop())
                    x-show="$store.sidebar.isOpen"
                @endif
                class="grid justify-items-start text-start"
            >
                <span class="text-gray-950 dark:text-white">
                    {{ $currentProject->name }}
                </span>
            </span>

            <x-filament::icon
                icon="heroicon-m-chevron-down"
                icon-alias="panels::tenant-menu.toggle-button"
                :x-show="filament()->isSidebarCollapsibleOnDesktop() ? '$store.sidebar.isOpen' : null"
                class="ms-auto h-5 w-5 shrink-0 text-gray-400 transition duration-75 group-hover:text-gray-500 group-focus-visible:text-gray-500 dark:text-gray-500 dark:group-hover:text-gray-400 dark:group-focus-visible:text-gray-400"
            />
        </button>
    </x-slot>

    <x-filament::dropdown.list>
        @foreach ($projects as $project)
            <x-filament::dropdown.list.item wire:click="updateProject({{ $project }})" class="cursor-pointer" tag="a">
                {{ $project->name }}
            </x-filament::dropdown.list.item>
        @endforeach
    </x-filament::dropdown.list>
</x-filament::dropdown>
