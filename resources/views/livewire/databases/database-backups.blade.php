<div x-data="">
    <x-card-header>
        <x-slot name="title">{{ __("Backups") }}</x-slot>
        <x-slot name="description">{{ __("You can backup your databases into external storages") }}</x-slot>
        <x-slot name="aside">
            <div>
                <x-primary-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'create-backup')">
                    {{ __('Create Backup') }}
                </x-primary-button>

                @include('livewire.databases.partials.create-backup-modal')
            </div>
        </x-slot>
    </x-card-header>
    @if(count($backups) > 0)
        <x-table>
            <tr>
                <x-th>{{ __("Database") }}</x-th>
                <x-th>{{ __("Created") }}</x-th>
                <x-th>{{ __("Status") }}</x-th>
                <x-th></x-th>
            </tr>
            @foreach($backups as $backup)
                <tr>
                    <x-td>{{ $backup->database->name }}</x-td>
                    <x-td>
                        <x-datetime :value="$backup->created_at" />
                    </x-td>
                    <x-td>
                        <div class="inline-flex">
                            @include('livewire.databases.partials.backup-status', ['status' => $backup->status])
                        </div>
                    </x-td>
                    <x-td class="flex w-full justify-end">
                        <x-icon-button :href="route('servers.databases.backups', ['server' => $server->id, 'backup' => $backup->id])">
                            Files
                        </x-icon-button>
                        <x-icon-button x-on:click="$wire.deleteId = '{{ $backup->id }}'; $dispatch('open-modal', 'delete-backup')">
                            Delete
                        </x-icon-button>
                    </x-td>
                </tr>
            @endforeach
        </x-table>
        @include('livewire.databases.partials.delete-backup-modal')
    @else
        <x-simple-card class="text-center">{{ __("You don't have any backups yet") }}</x-simple-card>
    @endif
</div>
