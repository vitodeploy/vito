<div x-data="{
    deleteAction: '',
    linkAction: '',
    linkedDatabases: [],
}">
    <x-card-header>
        <x-slot name="title">{{ __("Database Users") }}</x-slot>
        <x-slot name="description">
            {{ __("You can see and manage your database users here") }}
        </x-slot>
        <x-slot name="aside">
            <div>
                <x-primary-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'create-database-user')">
                    {{ __("Create Database User") }}
                </x-primary-button>
                @include("databases.partials.create-database-user-modal")
            </div>
        </x-slot>
    </x-card-header>
    <x-live id="live-database-users">
        @if (count($databaseUsers) > 0)
            <x-table>
                <x-tr>
                    <x-th>{{ __("Username") }}</x-th>
                    <x-th>{{ __("Created") }}</x-th>
                    <x-th class="flex items-center">
                        <x-heroicon name="o-link" class="mr-1 h-5 w-5" />
                        {{ __("Linked Databases") }}
                    </x-th>
                    <x-th>{{ __("Status") }}</x-th>
                    <x-th></x-th>
                </x-tr>
                @foreach ($databaseUsers as $databaseUser)
                    <x-tr>
                        <x-td>{{ $databaseUser->username }}</x-td>
                        <x-td>
                            <x-datetime :value="$databaseUser->created_at" />
                        </x-td>
                        <x-td>[{{ $databaseUser->databases ? implode(", ", $databaseUser->databases) : "-" }}]</x-td>
                        <x-td>
                            <div class="inline-flex">
                                @include("databases.partials.database-user-status", ["status" => $databaseUser->status])
                            </div>
                        </x-td>
                        <x-td class="text-right">
                            <x-icon-button
                                x-on:click="$dispatch('open-modal', 'database-user-password'); document.getElementById('txt-database-user-password').value = 'Loading...';"
                                hx-post="{{ route('servers.databases.users.password', ['server' => $server, 'databaseUser' => $databaseUser]) }}"
                                hx-target="#database-user-password-content"
                                hx-select="#database-user-password-content"
                                hx-swap="outerHTML"
                            >
                                <x-heroicon name="o-lock-closed" class="h-5 w-5" />
                            </x-icon-button>
                            <x-icon-button
                                x-on:click="linkAction = '{{ route('servers.databases.users.link', ['server' => $server, 'databaseUser' => $databaseUser]) }}';linkedDatabases = {{ json_encode($databaseUser->databases) }}; $dispatch('open-modal', 'link-database-user');"
                            >
                                <x-heroicon name="o-link" class="h-5 w-5" />
                            </x-icon-button>
                            <x-icon-button
                                x-on:click="deleteAction = '{{ route('servers.databases.users.destroy', ['server' => $server, 'databaseUser' => $databaseUser]) }}'; $dispatch('open-modal', 'delete-database-user')"
                            >
                                <x-heroicon name="o-trash" class="h-5 w-5" />
                            </x-icon-button>
                        </x-td>
                    </x-tr>
                @endforeach
            </x-table>
        @else
            <x-simple-card class="text-center">
                {{ __("You don't have any database users yet") }}
            </x-simple-card>
        @endif
    </x-live>
    <x-confirmation-modal
        name="delete-database-user"
        title="Confirm"
        description="Are you sure that you want to delete this user?"
        method="delete"
        x-bind:action="deleteAction"
    />
    @include("databases.partials.database-user-password-modal")
    @include("databases.partials.link-database-user-modal")
</div>
