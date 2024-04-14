<div x-data="{ deleteAction: '' }">
    <x-card-header>
        <x-slot name="title">{{ __("Databases") }}</x-slot>
        <x-slot name="description">
            {{ __("You can see and manage your databases here") }}
        </x-slot>
        <x-slot name="aside">
            <div>
                <x-primary-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'create-database')">
                    {{ __("Create Database") }}
                </x-primary-button>
                @include("databases.partials.create-database-modal")
            </div>
        </x-slot>
    </x-card-header>
    <x-live id="live-databases">
        @if (count($databases) > 0)
            <x-table>
                <x-tr>
                    <x-th>{{ __("Name") }}</x-th>
                    <x-th>{{ __("Created") }}</x-th>
                    <x-th>{{ __("Status") }}</x-th>
                    <x-th></x-th>
                </x-tr>
                @foreach ($databases as $database)
                    <x-tr>
                        <x-td>{{ $database->name }}</x-td>
                        <x-td>
                            <x-datetime :value="$database->created_at" />
                        </x-td>
                        <x-td>
                            <div class="inline-flex">
                                @include("databases.partials.database-status", ["status" => $database->status])
                            </div>
                        </x-td>
                        <x-td class="text-right">
                            <x-icon-button
                                x-on:click="deleteAction = '{{ route('servers.databases.destroy', ['server' => $server, 'database' => $database]) }}'; $dispatch('open-modal', 'delete-database')"
                            >
                                <x-heroicon name="o-trash" class="h-5 w-5" />
                            </x-icon-button>
                        </x-td>
                    </x-tr>
                @endforeach
            </x-table>
        @else
            <x-simple-card class="text-center">
                {{ __("You don't have any databases yet") }}
            </x-simple-card>
        @endif
    </x-live>
    <x-confirmation-modal
        name="delete-database"
        title="Confirm"
        description="Are you sure that you want to delete this database?"
        method="delete"
        x-bind:action="deleteAction"
    />
</div>
