<div x-data="">
    <div class="inline-flex gap-1">
        <div
            id="tags-list-{{ $taggable->id }}"
            class="inline-flex gap-1"
            @if (! isset($oobOff) || ! $oobOff)
                hx-swap-oob="outerHTML"
            @endif
        >
            @foreach ($taggable->tags as $tag)
                <div
                    class="border-{{ $tag->color }}-300 bg-{{ $tag->color }}-50 text-{{ $tag->color }}-500 dark:border-{{ $tag->color }}-600 dark:bg-{{ $tag->color }}-500 flex max-w-max items-center rounded-md border px-2 py-1 text-xs dark:bg-opacity-10"
                >
                    <x-heroicon name="o-tag" class="mr-1 size-4" />
                    {{ $tag->name }}
                </div>
            @endforeach
        </div>

        @if (isset($edit) && $edit)
            <a
                x-on:click="$dispatch('open-modal', 'manage-tags-modal')"
                class="flex max-w-max cursor-pointer items-center justify-center rounded-md border border-gray-300 bg-gray-50 px-2 py-1 text-xs text-gray-500 dark:border-gray-600 dark:bg-gray-500 dark:bg-opacity-10"
            >
                <x-heroicon name="o-pencil" class="h-3 w-3" />
            </a>
            @include("settings.tags.manage")
        @endif
    </div>
</div>
