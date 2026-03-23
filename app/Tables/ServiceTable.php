<?php

namespace App\Tables;

use App\Models\Service;

class ServiceTable extends AbstractTable
{
    protected string $pageName = 'servicesPage';

    protected ?string $realtimeEvent = 'service';

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
            Column::data('id'),
            Column::data('server_id'),
            Column::data('type'),
            Column::data('type_data'),
            Column::data('unit'),
            Column::data('is_default'),
            Column::data('installed_version'),
            Column::data('config_paths', fn (Service $service) => config("service.services.{$service->name}.config_paths", [])),
            Column::data('icon', fn (Service $service) => config('core.service_icons')[$service->name] ?? ''),
            Column::data('log', fn (Service $service) => $service->log ? [
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
