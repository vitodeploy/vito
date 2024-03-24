@props([
    "interval" => "7s",
    "id",
    "target" => null,
])

<div
    id="{{ $id }}"
    hx-get="{{ request()->getUri() }}"
    hx-trigger="every {{ $interval }}"
    @if ($target)
        hx-target="{{ $target }}"
        hx-select="{{ $target }}"
    @else
        hx-select="#{{ $id }}"
    @endif
    hx-swap="outerHTML"
>
    {{ $slot }}
</div>
