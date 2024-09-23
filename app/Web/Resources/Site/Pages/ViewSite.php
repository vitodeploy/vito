<?php

namespace App\Web\Resources\Site\Pages;

use App\Models\Server;
use App\Models\Site;
use App\Web\Resources\Site\SiteResource;
use App\Web\Traits\HasServerInfoWidget;
use Filament\Resources\Pages\Page;

class ViewSite extends Page
{
    use HasServerInfoWidget;

    protected static string $resource = SiteResource::class;

    protected static string $view = 'web.resources.site.pages.view';

    public Site $site;

    public function mount(Site $record): void
    {
        $this->site = $record;
    }

    protected function getHeaderActions(): array
    {
        return [

        ];
    }

    protected function getServer(): ?Server
    {
        return $this->site->server;
    }
}
