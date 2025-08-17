<?php

namespace App\Services\NodeJS;

use App\Exceptions\SSHError;
use App\Services\AbstractService;
use Closure;
use Illuminate\Validation\Rule;

class NodeJS extends AbstractService
{
    public static function id(): string
    {
        return 'nodejs';
    }

    public static function type(): string
    {
        return 'nodejs';
    }

    public function unit(): string
    {
        return '';
    }

    public function creationRules(array $input): array
    {
        return [
            'type' => [
                function (string $attribute, mixed $value, Closure $fail): void {
                    $exists = $this->service->server->nodejs();
                    if ($exists) {
                        $fail('You already have nodejs installed on the server.');
                    }
                },
            ],
            'version' => [
                'required',
                Rule::in(config('service.services.nodejs.versions')),
                Rule::unique('services', 'version')
                    ->where('type', 'nodejs')
                    ->where('server_id', $this->service->server_id),
            ],
        ];
    }

    public function deletionRules(): array
    {
        return [
            'service' => [
                function (string $attribute, mixed $value, Closure $fail): void {
                    $hasSite = $this->service->server->sites()
                        ->where('nodejs_version', $this->service->version)
                        ->exists();
                    if ($hasSite) {
                        $fail('Some sites are using this NodeJS version.');
                    }
                },
            ],
        ];
    }

    /**
     * @throws SSHError
     */
    public function install(): void
    {
        $server = $this->service->server;
        $server->ssh()->exec(
            view('ssh.services.nodejs.install-nodejs', [
                'version' => $this->service->version,
            ]),
            'install-nodejs-'.$this->service->version
        );
        event('service.installed', $this->service);
        $this->service->server->os()->cleanup();
    }

    /**
     * @throws SSHError
     */
    public function uninstall(): void
    {
        // skip uninstalling if there are other NodeJS services installed
        // to keep it compatible with the previous approach with nvm
        if ($this->service->server->services()->where('type', 'nodejs')->count() > 1) {
            return;
        }
        $this->service->server->ssh()->exec(
            view('ssh.services.nodejs.uninstall-nodejs', [
                'version' => $this->service->version,
                'default' => $this->service->is_default,
            ]),
            'uninstall-nodejs-'.$this->service->version
        );
        event('service.uninstalled', $this->service);
        $this->service->server->os()->cleanup();
    }

    public function version(): string
    {
        $version = $this->service->server->ssh()->exec(
            'node -v | tr -d \'v\''
        );

        return trim($version);
    }
}
