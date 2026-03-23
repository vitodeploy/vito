<?php

namespace App\Tables;

use App\Models\Service;

class PhpTable extends AbstractTable
{
    protected string $pageName = 'phpPage';

    protected ?string $realtimeEvent = 'service';

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            Column::make('version', 'Version')
                ->sortable(),
            Column::make('created_at', 'Installed at')
                ->sortable()
                ->date(),
            Column::make('is_default', 'Default cli')
                ->sortable()
                ->value(fn (Service $service) => $service->is_default ? 'Yes' : 'No')
                ->badge(
                    colorField: '_is_default_color',
                ),
            Column::data('_is_default_color', fn (Service $service) => $service->is_default ? 'default' : 'outline'),
            Column::make('status', 'Status')
                ->sortable()
                ->enum(),
            Column::data('id'),
            Column::data('server_id'),
            Column::data('type'),
            Column::data('type_data'),
            Column::data('unit'),
            Column::data('installed_version'),
        ];
    }
}
