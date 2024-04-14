<div x-data="{ deleteAction: '' }">
    <x-card-header>
        <x-slot name="title">{{ __("Backups") }}</x-slot>
        <x-slot name="description">
            {{ __("You can backup your databases into external storages") }}
        </x-slot>
        <x-slot name="aside">
            <div>
                @include("databases.partials.create-backup-modal")
            </div>
        </x-slot>
    </x-card-header>
    <x-live id="live-backups">
        @if (count($backups) > 0)
            <x-table>
                <x-tr>
                    <x-th>{{ __("Database") }}</x-th>
                    <x-th>{{ __("Created") }}</x-th>
                    <x-th>{{ __("Storage") }}</x-th>
                    <x-th>{{ __("Status") }}</x-th>
                    <x-th></x-th>
                </x-tr>
                @foreach ($backups as $backup)
                    <x-tr>
                        <x-td>{{ $backup->database->name }}</x-td>
                        <x-td>
                            <x-datetime :value="$backup->created_at" />
                        </x-td>
                        <x-td>{{ $backup->storage->profile }} ({{ $backup->storage->provider }})</x-td>
                        <x-td>
                            <div class="inline-flex">
                                @include("databases.partials.backup-status", ["status" => $backup->status])
                            </div>
                        </x-td>
                        <x-td class="text-right">
                            <x-icon-button
                                :href="route('servers.databases.backups', ['server' => $server, 'backup' => $backup])"
                            >
                                <x-heroicon name="o-circle-stack" class="h-5 w-5" />
                            </x-icon-button>
                            <x-icon-button
                                x-on:click="deleteAction = '{{ route('servers.databases.backups.destroy', ['server' => $server, 'backup' => $backup]) }}'; $dispatch('open-modal', 'delete-backup')"
                            >
                                <x-heroicon name="o-trash" class="h-5 w-5" />
                            </x-icon-button>
                        </x-td>
                    </x-tr>
                @endforeach
            </x-table>
        @else
            <x-simple-card class="text-center">
                {{ __("You don't have any backups yet") }}
            </x-simple-card>
        @endif
    </x-live>
    <x-confirmation-modal
        name="delete-backup"
        title="Confirm"
        description="Are you sure that you want to delete this backup?"
        method="delete"
        x-bind:action="deleteAction"
    />
</div>
