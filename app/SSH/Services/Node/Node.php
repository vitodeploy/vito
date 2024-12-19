<?php

namespace App\SSH\Services\Node;

use App\SSH\HasScripts;
use App\SSH\Services\AbstractService;
use Closure;
use Illuminate\Validation\Rule;

class Node extends AbstractService
{
    use HasScripts;

    public function creationRules(array $input): array
    {
        return [
            'version' => [
                'required',
                Rule::in(config('core.php_versions')),
                Rule::notIn([\App\Enums\NodeJS::NONE]),
                Rule::unique('services', 'version')
                    ->where('type', 'node')
                    ->where('server_id', $this->service->server_id),
            ],
        ];
    }

    public function deletionRules(): array
    {
        return [
            'service' => [
                function (string $attribute, mixed $value, Closure $fail) {
                    $hasSite = $this->service->server->sites()
                        ->where('node_version', $this->service->version)
                        ->exists();
                    if ($hasSite) {
                        $fail('Some sites are using this Node version.');
                    }
                },
            ],
        ];
    }

    public function install(): void
    {
        $server = $this->service->server;
        $server->ssh()->exec(
            $this->getScript('install-node.sh', [
                'version' => $this->service->version,
                'user' => $server->getSshUser(),
            ]),
            'install-node-'.$this->service->version
        );
        $this->service->server->os()->cleanup();
    }

    public function uninstall(): void
    {
        $this->service->server->ssh()->exec(
            $this->getScript('uninstall-node.sh', [
                'version' => $this->service->version,
            ]),
            'uninstall-node-'.$this->service->version
        );
        $this->service->server->os()->cleanup();
    }

    public function setDefaultCli(): void
    {
        $this->service->server->ssh()->exec(
            $this->getScript('change-default-node.sh', [
                'version' => $this->service->version,
            ]),
            'change-default-node'
        );
    }
}
