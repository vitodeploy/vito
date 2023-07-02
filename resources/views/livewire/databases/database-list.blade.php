<div x-data="">
    <x-card-header>
        <x-slot name="title">{{ __("Databases") }}</x-slot>
        <x-slot name="description">{{ __("You can see and manage your databases here") }}</x-slot>
        <x-slot name="aside">
            <div>
                <x-primary-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'create-database')">
                    {{ __('Create Database') }}
                </x-primary-button>

                @include('livewire.databases.partials.create-database-modal')
            </div>
        </x-slot>
    </x-card-header>
    @if(count($databases) > 0)
        <x-table>
            <tr>
                <x-td>{{ __("Name") }}</x-td>
                <x-td>{{ __("Created") }}</x-td>
                <x-td>{{ __("Status") }}</x-td>
                <x-td></x-td>
            </tr>
            @foreach($databases as $database)
                <tr>
                    <x-td>{{ $database->name }}</x-td>
                    <x-td>
                        <x-datetime :value="$database->created_at"/>
                    </x-td>
                    <x-td>
                        <div class="inline-flex">
                            @include('livewire.databases.partials.database-status', ['status' => $database->status])
                        </div>
                    </x-td>
                    <x-td class="flex w-full justify-end">
                        <x-icon-button x-on:click="$wire.deleteId = '{{ $database->id }}'; $dispatch('open-modal', 'delete-database')">
                            <x-heroicon-o-trash class="w-4 h-4" />
                        </x-icon-button>
                    </x-td>
                </tr>
            @endforeach
        </x-table>
       @include('livewire.databases.partials.delete-database-modal')
    @else
        <x-simple-card class="text-center">{{ __("You don't have any databases yet") }}</x-simple-card>
    @endif
</div>
