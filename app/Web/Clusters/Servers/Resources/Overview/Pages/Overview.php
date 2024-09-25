<?php

namespace App\Web\Clusters\Servers\Resources\Overview\Pages;

use App\Models\Server;
use App\Web\Clusters\Servers\Resources\Overview\OverviewResource;
use App\Web\Clusters\Servers\Resources\Overview\Widgets\Installing;
use App\Web\Traits\PageHasCluster;
use App\Web\Traits\PageHasServerInfoWidget;
use App\Web\Traits\PageHasWidgets;
use Filament\Resources\Pages\Page;
use Livewire\Attributes\On;

class Overview extends Page
{
    use PageHasCluster;
    use PageHasServerInfoWidget;
    use PageHasWidgets;

    protected static string $resource = OverviewResource::class;

    public Server $server;

    public string $previousStatus;

    public function mount(): void
    {
        $this->previousStatus = $this->server->status;
    }

    #[On('$refresh')]
    public function refresh(): void
    {
        $currentStatus = $this->server->refresh()->status;

        if ($this->previousStatus !== $currentStatus) {
            $this->redirect(static::$resource::getUrl(parameters: ['server' => $this->server]));
        }

        $this->previousStatus = $currentStatus;
    }

    public function getWidgets(): array
    {
        if ($this->server->isInstalling()) {
            return [
                [Installing::class, ['server' => $this->server]],
            ];
        }

        return [];
    }
}
