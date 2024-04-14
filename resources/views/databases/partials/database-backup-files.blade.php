<div x-data="{ restoreAction: '', deleteAction: '' }">
    <x-card-header>
        <x-slot name="title">{{ __("Backup Files") }}</x-slot>
        <x-slot name="description">
            {{ __("Here you can see your backup files") }}
        </x-slot>
        <x-slot name="aside">
            <div>
                <x-secondary-button :href="route('servers.databases', ['server' => $server])">
                    {{ __("Back to Databases") }}
                </x-secondary-button>
                <x-primary-button
                    class="ml-1"
                    :href="route('servers.databases.backups.run', ['server' => $server, 'backup' => $backup])"
                >
                    {{ __("Backup Now") }}
                </x-primary-button>
            </div>
        </x-slot>
    </x-card-header>
    <x-live id="live-backup-files">
        @if (count($files) > 0)
            <x-table class="mt-5">
                <x-tr>
                    <x-th>{{ __("Name") }}</x-th>
                    <x-th>{{ __("Created") }}</x-th>
                    {{-- <x-th>{{ __("Size") }}</x-th> --}}
                    <x-th>{{ __("Status") }}</x-th>
                    <x-th>{{ __("Restored") }}</x-th>
                    <x-th>{{ __("Restored To") }}</x-th>
                    <x-th></x-th>
                </x-tr>
                @foreach ($files as $file)
                    <x-tr>
                        <x-td>{{ $file->name }}</x-td>
                        <x-td>
                            <x-datetime :value="$file->created_at" />
                        </x-td>
                        {{-- <x-td>{{ $file->size }}</x-td> --}}
                        <x-td>
                            <div class="inline-flex">
                                @include("databases.partials.backup-file-status", ["status" => $file->status])
                            </div>
                        </x-td>
                        <x-td>
                            @if ($file->restored_at)
                                <x-datetime :value="$file->restored_at" />
                            @else
                                -
                            @endif
                        </x-td>
                        <x-td>
                            @if ($file->restored_to)
                                {{ $file->restored_to }}
                            @else
                                -
                            @endif
                        </x-td>
                        <x-td class="text-right">
                            @if (in_array($file->status, [\App\Enums\BackupFileStatus::CREATED, \App\Enums\BackupFileStatus::RESTORED, \App\Enums\BackupFileStatus::RESTORE_FAILED]))
                                <x-icon-button
                                    x-on:click="restoreAction = '{{ route('servers.databases.backups.files.restore', ['server' => $server, 'backup' => $backup, 'backupFile' => $file]) }}'; $dispatch('open-modal', 'restore-backup')"
                                >
                                    <x-heroicon name="o-arrow-path" class="h-5 w-5" />
                                </x-icon-button>
                            @endif

                            <x-icon-button
                                x-on:click="deleteAction = '{{ route('servers.databases.backups.files.destroy', ['server' => $server, 'backup' => $backup, 'backupFile' => $file]) }}'; $dispatch('open-modal', 'delete-backup-file')"
                            >
                                <x-heroicon name="o-trash" class="h-5 w-5" />
                            </x-icon-button>
                        </x-td>
                    </x-tr>
                @endforeach
            </x-table>
            <div class="mt-5">
                {{ $files->withQueryString()->links() }}
            </div>
        @else
            <x-simple-card class="text-center">
                {{ __("You don't have any backups yet") }}
            </x-simple-card>
        @endif
    </x-live>
    @include("databases.partials.restore-backup-modal", ["databases" => $server->databases])
    <x-confirmation-modal
        name="delete-backup-file"
        :title="__('Confirm')"
        :description="__('Are you sure that you want to delete this file?')"
        method="delete"
        x-bind:action="deleteAction"
    />
</div>
