<div x-data="serverCombobox()">
    <div class="relative">
        <div
            @click="open = !open"
            class="text-md z-0 flex h-10 w-full cursor-pointer items-center rounded-md bg-gray-900 px-4 py-3 pr-10 leading-5 text-gray-100 focus:ring-1 focus:ring-gray-700"
            x-text="selected.name ?? 'Select Server'"
        ></div>
        <button type="button" @click="open = !open" class="absolute inset-y-0 right-0 z-0 flex items-center pr-2">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
                fill="currentColor"
                aria-hidden="true"
                class="h-5 w-5 text-gray-400"
            >
                <path
                    fill-rule="evenodd"
                    d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z"
                    clip-rule="evenodd"
                ></path>
            </svg>
        </button>
        <div
            x-show="open"
            @click.away="open = false"
            class="absolute z-10 mt-1 w-full overflow-auto rounded-md bg-white pb-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-gray-700 sm:text-sm"
        >
            <div class="relative p-2">
                <input
                    x-model="query"
                    @input="filterServersAndOpen"
                    placeholder="Filter"
                    class="dark:focus:ring-800 w-full rounded-md bg-gray-200 py-2 pl-3 pr-10 text-sm leading-5 focus:ring-1 focus:ring-gray-400 dark:bg-gray-900 dark:text-gray-100"
                />
            </div>
            <div class="relative max-h-[350px] overflow-y-auto">
                <template x-for="(server, index) in filteredServers" :key="index">
                    <div
                        @click="selectServer(server); open = false"
                        :class="server.id === selected.id ? 'cursor-default bg-primary-600 text-white' : 'cursor-pointer'"
                        class="relative select-none px-4 py-2 text-gray-700 hover:bg-primary-600 hover:text-white dark:text-white"
                    >
                        <span class="block truncate" x-text="server.name"></span>
                        <template x-if="server.id === selected.id">
                            <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-white">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                    aria-hidden="true"
                                    class="h-5 w-5"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z"
                                        clip-rule="evenodd"
                                    ></path>
                                </svg>
                            </span>
                        </template>
                    </div>
                </template>
            </div>
            <div
                x-show="filteredServers.length === 0"
                class="relative block cursor-default select-none truncate px-4 py-2 text-gray-700 dark:text-white"
            >
                No servers found!
            </div>
            <div class="py-1">
                <hr class="border-gray-300 dark:border-gray-600" />
            </div>
            <div>
                <a
                    href="{{ route("servers") }}"
                    class="@if(request()->routeIs('servers')) cursor-default bg-primary-600 text-white @else cursor-pointer @endif relative block select-none px-4 py-2 text-gray-700 hover:bg-primary-600 hover:text-white dark:text-white"
                >
                    <span class="block truncate">Servers List</span>
                </a>
            </div>
            <div>
                <a
                    href="{{ route("servers.create") }}"
                    class="@if(request()->routeIs('servers.create')) cursor-default bg-primary-600 text-white @else cursor-pointer @endif relative block select-none px-4 py-2 text-gray-700 hover:bg-primary-600 hover:text-white dark:text-white"
                >
                    <span class="block truncate">Create a Server</span>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    function serverCombobox() {
        const servers = @json(auth()->user()->currentProject->servers()->select('id', 'name')->get());
        return {
            open: false,
            query: '',
            servers: servers,
            selected: @if(isset($server)) @json($server->only('id', 'name')) @else {} @endif,
            filteredServers: servers,
            selectServer(server) {
                if (this.selected.id !== server.id) {
                    this.selected = server;
                    window.location.href = '{{ url('/servers/') }}/' + server.id
                }
            },
            filterServersAndOpen() {
                if (this.query === '') {
                    this.filteredServers = this.servers;
                    this.open = false;
                } else {
                    this.filteredServers = this.servers.filter((server) =>
                        server.name
                            .toLowerCase()
                            .replace(/\s+/g, '')
                            .includes(this.query.toLowerCase().replace(/\s+/g, ''))
                    );
                    this.open = true;
                }
            },
        };
    }
</script>
