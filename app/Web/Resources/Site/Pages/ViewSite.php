<?php

namespace App\Web\Resources\Site\Pages;

use App\Models\Server;
use App\Models\Site;
use App\Web\Resources\Site\SiteResource;
use App\Web\Traits\HasServerInfoWidget;
use App\Web\Traits\PageHasWidgets;
use Filament\Resources\Pages\Page;

class ViewSite extends Page
{
    use HasServerInfoWidget;
    use PageHasWidgets;

    protected static string $resource = SiteResource::class;

    protected static string $view = 'web.components.page';

    public Site $site;

    public function getWidgets(): array
    {
        return [];
    }

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
