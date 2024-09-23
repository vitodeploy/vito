@php
    $color = match ($status) {
        \App\Enums\ServerStatus::READY => "success",
        \App\Enums\ServerStatus::INSTALLING => "warning",
        \App\Enums\ServerStatus::DISCONNECTED => "disabled",
        \App\Enums\ServerStatus::INSTALLATION_FAILED => "danger",
        default => "gray",
    };
@endphp

<x-filament::badge :color="$color">{{ $status }}</x-filament::badge>
