<x-container>
    <x-card-header>
        <x-slot name="title">Scripts</x-slot>
        <x-slot name="description">Your scripts are here. Create/Edit/Delete and Execute them on your servers.</x-slot>
        <x-slot name="aside">
            @include("scripts.partials.create-script")
        </x-slot>
    </x-card-header>

    @if (count($scripts) > 0)
        <div id="scripts-list" x-data="{ deleteAction: '' }">
            <x-table>
                <x-thead>
                    <x-tr>
                        <x-th>ID</x-th>
                        <x-th>Name</x-th>
                        <x-th>Last Executed At</x-th>
                        <x-th></x-th>
                    </x-tr>
                </x-thead>
                <x-tbody>
                    @foreach ($scripts as $script)
                        <x-tr>
                            <x-td>{{ $script->id }}</x-td>
                            <x-td>{{ $script->name }}</x-td>
                            <x-td>
                                @if ($script->lastExecution)
                                    <x-datetime :value="$script->lastExecution->created_at" />
                                @else
                                    -
                                @endif
                            </x-td>
                            <x-td class="text-right">
                                <x-icon-button :href="route('scripts.show', $script)" data-tooltip="Executions">
                                    <x-heroicon name="o-eye" class="h-5 w-5" />
                                </x-icon-button>
                                <x-icon-button
                                    data-tooltip="Execute"
                                    id="execute-{{ $script->id }}"
                                    hx-get="{{ route('scripts.index', ['execute' => $script->id]) }}"
                                    hx-replace-url="true"
                                    hx-select="#execute"
                                    hx-target="#execute"
                                    hx-ext="disable-element"
                                    hx-disable-element="#execute-{{ $script->id }}"
                                >
                                    <x-heroicon name="o-bolt" class="h-5 w-5 text-primary-500" />
                                </x-icon-button>
                                <x-icon-button
                                    data-tooltip="Edit"
                                    id="edit-{{ $script->id }}"
                                    hx-get="{{ route('scripts.index', ['edit' => $script->id]) }}"
                                    hx-replace-url="true"
                                    hx-select="#edit"
                                    hx-target="#edit"
                                    hx-ext="disable-element"
                                    hx-disable-element="#edit-{{ $script->id }}"
                                >
                                    <x-heroicon name="o-pencil" class="h-5 w-5" />
                                </x-icon-button>
                                <x-icon-button
                                    data-tooltip="Delete"
                                    x-on:click="deleteAction = '{{ route('scripts.delete', $script->id) }}'; $dispatch('open-modal', 'delete-script')"
                                >
                                    <x-heroicon name="o-trash" class="h-5 w-5" />
                                </x-icon-button>
                            </x-td>
                        </x-tr>
                    @endforeach
                </x-tbody>
            </x-table>

            @include("scripts.partials.delete-script")

            <div id="edit">
                @if (isset($editScript))
                    @include("scripts.partials.edit-script", ["script" => $editScript])
                @endif
            </div>

            <div id="execute">
                @if (isset($executeScript))
                    @include("scripts.partials.execute-script", ["script" => $executeScript])
                @endif
            </div>
        </div>
    @else
        <x-simple-card>
            <div class="text-center">
                {{ __("You don't have any scripts yet!") }}
            </div>
        </x-simple-card>
    @endif
</x-container>
