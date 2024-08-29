<x-modal name="manage-tags-modal">
    <div class="p-6">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Manage Tags</h2>
            @include("settings.tags.attach", ["taggable" => $taggable])
        </div>

        <x-table id="tags-{{ $taggable->id }}" class="mt-6" hx-swap-oob="outerHTML">
            <x-thead>
                <x-tr>
                    <x-th>Name</x-th>
                    <x-th></x-th>
                </x-tr>
            </x-thead>
            <x-tbody>
                @foreach ($taggable->tags as $tag)
                    <x-tr>
                        <x-td>
                            <div class="flex items-center">
                                <div class="bg-{{ $tag->color }}-500 mr-1 h-3 w-3 rounded-full"></div>
                                {{ $tag->name }}
                            </div>
                        </x-td>
                        <x-td class="text-right">
                            <form
                                id="detach-tag-{{ $tag->id }}"
                                hx-post="{{ route("tags.detach", ["tag" => $tag]) }}"
                                hx-swap="outerHTML"
                            >
                                <input type="hidden" name="taggable_type" value="{{ get_class($taggable) }}" />
                                <input type="hidden" name="taggable_id" value="{{ $taggable->id }}" />
                                <x-icon-button>
                                    <x-heroicon name="o-trash" class="h-5 w-5 text-gray-500 dark:text-gray-400" />
                                </x-icon-button>
                            </form>
                        </x-td>
                    </x-tr>
                @endforeach
            </x-tbody>
        </x-table>
    </div>
</x-modal>
