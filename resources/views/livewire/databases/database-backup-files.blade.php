<div x-data="">
    <x-card-header>
        <x-slot name="title">{{ __("Backup Files") }}</x-slot>
        <x-slot name="description">{{ __("Here you can see your backup files") }}</x-slot>
        <x-slot name="aside">
            <div>
                <x-secondary-button :href="route('servers.databases', ['server' => $server])">
                    {{ __('Back to Databases') }}
                </x-secondary-button>
                <x-primary-button class="ml-1" wire:click="backup" wire:loading.attr="disabled">
                    {{ __("Backup Now") }}
                </x-primary-button>
            </div>
        </x-slot>
    </x-card-header>
    @if(count($files) > 0)
        <x-table class="mt-5">
            <tr>
                <x-th>{{ __("Name") }}</x-th>
                <x-th>{{ __("Created") }}</x-th>
                {{--<x-th>{{ __("Size") }}</x-th>--}}
                <x-th>{{ __("Status") }}</x-th>
                <x-th>{{ __("Restored") }}</x-th>
                <x-th>{{ __("Restored To") }}</x-th>
                <x-th></x-th>
            </tr>
            @foreach($files as $file)
                <tr>
                    <x-td>{{ $file->name }}</x-td>
                    <x-td>
                        <x-datetime :value="$file->created_at" />
                    </x-td>
                    {{--<x-td>{{ $file->size }}</x-td>--}}
                    <x-td>
                        <div class="inline-flex">
                            @include('livewire.databases.partials.backup-file-status', ['status' => $file->status])
                        </div>
                    </x-td>
                    <x-td>
                        @if($file->restored_at)
                            <x-datetime :value="$file->restored_at" />
                        @else
                            -
                        @endif
                    </x-td>
                    <x-td>
                        @if($file->restored_to)
                            {{ $file->restored_to }}
                        @else
                            -
                        @endif
                    </x-td>
                    <x-td class="flex w-full justify-end">
                        @if(in_array($file->status, [\App\Enums\BackupFileStatus::CREATED, \App\Enums\BackupFileStatus::RESTORED, \App\Enums\BackupFileStatus::RESTORE_FAILED]))
                            <x-icon-button x-on:click="$wire.restoreId = '{{ $file->id }}'; $dispatch('open-modal', 'restore-backup')">
                                Restore
                            </x-icon-button>
                        @endif
                        <x-icon-button x-on:click="$wire.deleteId = '{{ $file->id }}'; $dispatch('open-modal', 'delete-backup-file')">
                            Delete
                        </x-icon-button>
                    </x-td>
                </tr>
            @endforeach
        </x-table>
        <div class="mt-5">
            {{ $files->withQueryString()->links() }}
        </div>
        @include('livewire.databases.partials.restore-backup-modal', ['databases' => $server->databases])
        @include('livewire.databases.partials.delete-backup-file-modal')
    @else
        <x-simple-card class="text-center">{{ __("You don't have any backups yet") }}</x-simple-card>
    @endif
</div>
