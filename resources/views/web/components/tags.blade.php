<div class="inline-flex gap-1">
    @php
        if (! isset($tags) && isset($getRecord)) {
            $tags = $getRecord()->tags;
        }
    @endphp

    @foreach ($tags as $tag)
        <div
            class="border-{{ $tag->color }}-300 bg-{{ $tag->color }}-50 text-{{ $tag->color }}-500 dark:border-{{ $tag->color }}-600 dark:bg-{{ $tag->color }}-500 flex max-w-max items-center rounded-md border px-2 py-1 text-xs dark:bg-opacity-10"
        >
            <x-heroicon-o-tag class="mr-1 size-4" />
            {{ $tag->name }}
        </div>
    @endforeach
</div>
