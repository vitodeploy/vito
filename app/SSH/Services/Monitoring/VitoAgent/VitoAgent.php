<?php

namespace App\SSH\Services\Monitoring\VitoAgent;

use App\Models\Metric;
use App\SSH\HasScripts;
use App\SSH\Services\AbstractService;
use Closure;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Ramsey\Uuid\Uuid;

class VitoAgent extends AbstractService
{
    use HasScripts;

    const TAGS_URL = 'https://api.github.com/repos/vitodeploy/agent/tags';

    const DOWNLOAD_URL = 'https://github.com/vitodeploy/agent/releases/download/%s';

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
            'url' => '',
            'secret' => Uuid::uuid4()->toString(),
            'data_retention' => 10,
        ];
    }

    public function data(): array
    {
        return [
            'url' => $this->service->type_data['url'] ?? null,
            'secret' => $this->service->type_data['secret'] ?? null,
            'data_retention' => $this->service->type_data['data_retention'] ?? 10,
        ];
    }

    public function install(): void
    {
        $tags = Http::get(self::TAGS_URL)->json();
        if (empty($tags)) {
            throw new \Exception('Failed to fetch tags');
        }
        $this->service->version = $tags[0]['name'];
        $this->service->save();
        $downloadUrl = sprintf(self::DOWNLOAD_URL, $this->service->version);

        $data = $this->data();
        $data['url'] = route('api.servers.agent', [$this->service->server, $this->service->id]);
        $this->service->type_data = $data;
        $this->service->save();
        $this->service->refresh();

        $this->service->server->ssh()->exec(
            $this->getScript('install.sh', [
                'download_url' => $downloadUrl,
                'config_url' => $this->data()['url'],
                'config_secret' => $this->data()['secret'],
            ]),
            'install-vito-agent'
        );
        $status = $this->service->server->systemd()->status($this->service->unit);
        $this->service->validateInstall($status);
    }

    public function uninstall(): void
    {
        $this->service->server->ssh()->exec(
            $this->getScript('uninstall.sh'),
            'uninstall-vito-agent'
        );
        Metric::where('server_id', $this->service->server_id)->delete();
    }
}
