<div>
    @if($site->status === \App\Enums\SiteStatus::INSTALLING)
        @include('livewire.sites.partials.installing', ['site' => $site])

        <livewire:server-logs.logs-list :server="$site->server" :site="$site" :count="10" />
    @endif
    @if($site->status === \App\Enums\SiteStatus::INSTALLATION_FAILED)
        @include('livewire.sites.partials.installation-failed', ['site' => $site])

        <livewire:server-logs.logs-list :server="$site->server" :site="$site" :count="10" />
    @endif
    @if($site->status === \App\Enums\SiteStatus::READY)
        @if($site->type == \App\Enums\SiteType::LARAVEL)
            <livewire:application.laravel-app :site="$site" />
        @endif

        @if($site->type == \App\Enums\SiteType::PHP)
            <livewire:application.php-app :site="$site" />
        @endif

        @if($site->type == \App\Enums\SiteType::WORDPRESS)
            <livewire:application.wordpress-app :site="$site" />
        @endif
    @endif
</div>
