<div class="inline-flex gap-1">
    @php
        if (! isset($tags) && isset($getRecord)) {
            $tags = $getRecord()->tags;
        }
    @endphp

    @if (count($tags) === 0)
        -
    @endif

    @foreach ($tags as $tag)
        <x-filament::badge :color="$tag->color" icon="heroicon-o-tag">
            {{ $tag->name }}
        </x-filament::badge>
    @endforeach
</div>
