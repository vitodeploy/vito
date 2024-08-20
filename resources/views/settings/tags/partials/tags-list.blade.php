<div>
    <x-card-header>
        <x-slot name="title">{{ __("Tags") }}</x-slot>
        <x-slot name="description">
            {{ __("You can manage tags here") }}
        </x-slot>
        <x-slot name="aside">
            @include("settings.tags.partials.create-tag")
        </x-slot>
    </x-card-header>
    <div x-data="{ deleteAction: '' }" class="space-y-3">
        @if (count($tags) > 0)
            <x-table class="mt-6">
                <x-thead>
                    <x-tr>
                        <x-th>Name</x-th>
                        <x-th></x-th>
                    </x-tr>
                </x-thead>
                <x-tbody>
                    @foreach ($tags as $tag)
                        <x-tr>
                            <x-td>
                                <div class="flex items-center">
                                    <div class="bg-{{ $tag->color }}-500 mr-1 size-4 rounded-full"></div>
                                    {{ $tag->name }}
                                </div>
                            </x-td>
                            <x-td class="text-right">
                                <div class="inline">
                                    <x-icon-button
                                        id="edit-{{ $tag->id }}"
                                        hx-get="{{ route('settings.tags', ['edit' => $tag->id]) }}"
                                        hx-replace-url="true"
                                        hx-select="#edit"
                                        hx-target="#edit"
                                        hx-ext="disable-element"
                                        hx-disable-element="#edit-{{ $tag->id }}"
                                    >
                                        <x-heroicon name="o-pencil" class="h-5 w-5" />
                                    </x-icon-button>
                                    <x-icon-button
                                        x-on:click="deleteAction = '{{ route('settings.tags.delete', ['tag' => $tag]) }}'; $dispatch('open-modal', 'delete-tag')"
                                    >
                                        <x-heroicon name="o-trash" class="h-5 w-5" />
                                    </x-icon-button>
                                </div>
                            </x-td>
                        </x-tr>
                    @endforeach
                </x-tbody>
            </x-table>

            @include("settings.tags.partials.delete-tag")

            <div id="edit">
                @if (isset($editTag))
                    @include("settings.tags.partials.edit-tag", ["tag" => $editTag])
                @endif
            </div>
        @else
            <x-simple-card>
                <div class="text-center">
                    {{ __("You don't have any tags yet!") }}
                </div>
            </x-simple-card>
        @endif
    </div>
</div>
