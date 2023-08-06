<div x-data="">
    <x-card-header>
        <x-slot name="title">{{ __("Database Users") }}</x-slot>
        <x-slot name="description">{{ __("You can see and manage your database users here") }}</x-slot>
        <x-slot name="aside">
            <div>
                <x-primary-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'create-database-user')">
                    {{ __('Create Database User') }}
                </x-primary-button>

                @include('livewire.databases.partials.create-database-user-modal')
            </div>
        </x-slot>
    </x-card-header>
    @if(count($databaseUsers) > 0)
        <x-table>
            <tr>
                <x-td>{{ __("Username") }}</x-td>
                <x-td>{{ __("Created") }}</x-td>
                <x-td>{{ __("Linked Databases") }}</x-td>
                <x-td>{{ __("Status") }}</x-td>
                <x-td></x-td>
            </tr>
            @foreach($databaseUsers as $databaseUser)
                <tr>
                    <x-td>{{ $databaseUser->username }}</x-td>
                    <x-td>
                        <x-datetime :value="$databaseUser->created_at" />
                    </x-td>
                    <x-td>[{{ $databaseUser->databases ? implode(', ', $databaseUser->databases) : '-' }}]</x-td>
                    <x-td>
                        <div class="inline-flex">
                            @include('livewire.databases.partials.database-user-status', ['status' => $databaseUser->status])
                        </div>
                    </x-td>
                    <x-td class="flex w-full justify-end">
                        <x-icon-button x-on:click="$wire.deleteId = '{{ $databaseUser->id }}'; $dispatch('open-modal', 'delete-database-user')">
                            Delete
                        </x-icon-button>
                        <x-icon-button wire:click="viewPassword({{ $databaseUser->id }})">
                            View
                        </x-icon-button>
                        <x-icon-button wire:click="showLink({{ $databaseUser->id }})">
                            Link
                        </x-icon-button>
                    </x-td>
                </tr>
            @endforeach
        </x-table>
        @include('livewire.databases.partials.delete-database-user-modal')
        @include('livewire.databases.partials.database-user-password-modal')
        @include('livewire.databases.partials.link-database-user-modal')
    @else
        <x-simple-card class="text-center">{{ __("You don't have any database users yet") }}</x-simple-card>
    @endif
</div>
