<?php

namespace App\SSH\Services\Monitoring\RemoteMonitor;

use App\Models\Metric;
use App\SSH\Services\AbstractService;
use Closure;
use Illuminate\Validation\Rule;

class RemoteMonitor extends AbstractService
{
    public function creationRules(array $input): array
    {
        return [
            'type' => [
                function (string $attribute, mixed $value, Closure $fail) {
                    $monitoringExists = $this->service->server->monitoring();
                    if ($monitoringExists) {
                        $fail('You already have a monitoring service on the server.');
                    }
                },
            ],
            'version' => [
                'required',
                Rule::in(['latest']),
            ],
        ];
    }

    public function creationData(array $input): array
    {
        return [
            'data_retention' => 10,
        ];
    }

    public function data(): array
    {
        return [
            'data_retention' => $this->service->type_data['data_retention'] ?? 10,
        ];
    }

    public function install(): void
    {
        //
    }

    public function uninstall(): void
    {
        Metric::where('server_id', $this->service->server_id)->delete();
    }
}
