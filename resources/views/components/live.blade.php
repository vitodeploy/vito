@props([
    "interval" => "7s",
    "id",
    "target" => null,
])

<div
    {{ $attributes->merge(["interval" => $interval, "id" => $id, "hx-get" => request()->getUri(), "hx-trigger" => "every " . $interval, "hx-swap" => "outerHTML"]) }}
    @if ($target)
        hx-target="{{ $target }}"
        hx-select="{{ $target }}"
    @else
        hx-select="#{{ $id }}"
    @endif
>
    {{ $slot }}
</div>
