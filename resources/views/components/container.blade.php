@php
    $class = "mx-auto px-4 sm:px-6 lg:px-8";
    if (! str($attributes->get("class"))->contains("max-w-")) {
        $class .= " max-w-7xl";
    }
@endphp

<div {!! $attributes->merge(["class" => $class]) !!}>
    {{ $slot }}
</div>
