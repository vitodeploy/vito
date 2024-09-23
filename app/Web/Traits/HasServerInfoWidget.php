<?php

namespace App\Web\Traits;

use App\Models\Server;
use App\Web\Resources\Server\Widgets\ServerSummary;

trait HasServerInfoWidget
{
    protected function getServer(): ?Server
    {
        if (session()->has('current_server_id')) {
            $server = Server::query()->find(session()->get('current_server_id'));
            if ($server && auth()->user()->can('view', $server)) {
                return $server;
            }
        }

        return null;
    }

    protected function getHeaderWidgets(): array
    {
        $server = $this->getServer();
        $widgets = [];
        if ($server) {
            $widgets[] = ServerSummary::make([
                'server' => $server,
            ]);
        }

        return $widgets;
    }

    public function getHeaderWidgetsColumns(): int|string|array
    {
        return 1;
    }
}
