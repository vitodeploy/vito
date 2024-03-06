<div>
    @if ($site->status == \App\Enums\SiteStatus::READY)
        <x-status status="success">{{ $site->status }}</x-status>
    @endif

    @if ($site->status == \App\Enums\SiteStatus::INSTALLING)
        <x-status status="warning">{{ $site->status }}</x-status>
    @endif

    @if ($site->status == \App\Enums\SiteStatus::DELETING)
        <x-status status="danger">{{ $site->status }}</x-status>
    @endif

    @if ($site->status == \App\Enums\SiteStatus::INSTALLATION_FAILED)
        <x-status status="danger">{{ $site->status }}</x-status>
    @endif
</div>
