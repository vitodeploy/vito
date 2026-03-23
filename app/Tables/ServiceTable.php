<?php

namespace App\Tables;

use App\Models\Service;

class ServiceTable extends AbstractTable
{
    protected string $pageName = 'servicesPage';

    /**
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            Column::make('name', 'Name')
                ->sortable(),
            Column::make('version', 'Version')
                ->sortable(),
            Column::make('created_at', 'Installed at')
                ->sortable()
                ->date(),
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
            Column::make('is_default', '')
                ->hidden(),
            Column::make('installed_version', '')
                ->hidden(),
            Column::make('config_paths', '')
                ->hidden()
                ->value(fn (Service $service) => config("service.services.{$service->name}.config_paths", [])),
            Column::make('icon', '')
                ->hidden()
                ->value(fn (Service $service) => config('core.service_icons')[$service->name] ?? ''),
            Column::make('log', '')
                ->hidden()
                ->value(fn (Service $service) => $service->log ? [
                    'id' => $service->log->id,
                    'server_id' => $service->log->server_id,
                    'site_id' => $service->log->site_id,
                    'type' => $service->log->type,
                    'name' => $service->log->name,
                    'disk' => $service->log->disk,
                    'is_remote' => $service->log->is_remote,
                    'created_at' => $service->log->created_at,
                    'updated_at' => $service->log->updated_at,
                ] : null),
        ];
    }
}
