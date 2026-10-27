<?php

namespace App\Actions\EnvironmentAttachment;

use App\Enums\DatabaseStatus;
use App\Enums\ServerStatus;
use App\Enums\ServiceStatus;
use App\Models\Environment;
use App\Models\Service;

class GetEnvironmentResourceOptions
{
    /** @return array<string, mixed> */
    public function get(Environment $environment): array
    {
        $services = Service::query()->whereHas('server', fn ($query) => $query->where('project_id', $environment->application->project_id)->where('status', ServerStatus::READY))
            ->whereIn('name', ['mysql', 'mariadb', 'postgresql', 'redis', 'valkey'])->where('status', ServiceStatus::READY)
            ->with(['server.databases' => fn ($query) => $query->where('status', DatabaseStatus::READY)])->get();

        return ['services' => $services->map(fn (Service $service): array => [
            'id' => $service->id, 'name' => $service->name, 'type' => $service->type,
            'server_id' => $service->server_id, 'server_name' => $service->server->name,
            'databases' => $service->type === 'database' ? $service->server->databases->map(fn ($database): array => ['id' => $database->id, 'name' => $database->name])->all() : [],
            'charsets' => $service->type_data['charsets'] ?? [], 'default_charset' => $service->type_data['defaultCharset'] ?? '',
        ])->all()];
    }
}
