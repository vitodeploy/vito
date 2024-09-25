<?php

namespace App\Web\Traits;

use App\Web\Resources\Server\Widgets\ServerSummary;

trait PageHasServerInfoWidget
{
    protected function getHeaderWidgets(): array
    {
        $server = static::$resource::getServerFromRoute();
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
