@php
    $class = "mx-auto";
    if (! str($attributes->get("class"))->contains("max-w-")) {
        $class .= " max-w-7xl";
    }
@endphp

<div {!! $attributes->merge(["class" => $class]) !!}>
    {{ $slot }}
</div>
