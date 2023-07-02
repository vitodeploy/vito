<x-site-layout :site="$site">
    <x-slot name="pageTitle">{{ __("Application") }}</x-slot>

    @if($site->type == \App\Enums\SiteType::LARAVEL)
        <livewire:application.laravel-app :site="$site" />
    @endif

    @if($site->type == \App\Enums\SiteType::PHP)
        <livewire:application.php-app :site="$site" />
    @endif

    @if($site->type == \App\Enums\SiteType::WORDPRESS)
        <livewire:application.wordpress-app :site="$site" />
    @endif
</x-site-layout>
