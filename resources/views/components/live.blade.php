@props(['interval' => '10s', 'id'])

<div
    id="{{ $id }}"
    hx-get="{{ request()->getUri() }}"
    hx-trigger="every {{ $interval }}"
    hx-select="#{{ $id }}"
    hx-swap="outerHTML"
>
    {{ $slot }}
</div>
