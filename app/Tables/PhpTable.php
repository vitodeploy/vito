<?php

namespace App\Tables;

use App\Models\Service;

class PhpTable extends AbstractTable
{
    protected string $pageName = 'phpPage';

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
            Column::make('_is_default_color', '')
                ->hidden()
                ->value(fn (Service $service) => $service->is_default ? 'default' : 'outline'),
            Column::make('status', 'Status')
                ->sortable()
                ->enum(),
            Column::make('id', '')
                ->hidden(),
            Column::make('server_id', '')
                ->hidden(),
            Column::make('type', '')
                ->hidden(),
            Column::make('type_data', '')
                ->hidden(),
            Column::make('unit', '')
                ->hidden(),
            Column::make('installed_version', '')
                ->hidden(),
        ];
    }
}
