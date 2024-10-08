<?php

namespace App\Web\Pages\Servers\Metrics;

use App\Models\Metric;
use App\Web\Pages\Servers\Page;

class Index extends Page
{
    protected static ?string $slug = 'servers/{server}/metrics';

    protected static ?string $title = 'Metrics';

    public function mount(): void
    {
        $this->authorize('viewAny', [Metric::class, $this->server]);
    }

    public function getWidgets(): array
    {
        return [
            [Widgets\FilterForm::class, ['server' => $this->server]],
            [Widgets\MetricDetails::class, ['server' => $this->server]],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
