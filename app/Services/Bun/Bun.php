<?php

namespace App\Services\Bun;

use App\Exceptions\SSHError;
use App\Services\AbstractService;
use Closure;
use Illuminate\Validation\Rule;

class Bun extends AbstractService
{
    public static function id(): string
    {
        return 'bun';
    }

    public static function type(): string
    {
        return 'bun';
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
                    $exists = $this->service->server->bun();
                    if ($exists) {
                        $fail('You already have Bun installed on the server.');
                    }
                },
            ],
            'version' => [
                'required',
                Rule::in(config('service.services.bun.versions')),
                Rule::unique('services', 'version')
                    ->where('type', 'bun')
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
                        ->where('type', 'bun')
                        ->exists();
                    if ($hasSite) {
                        $fail('Some sites are using Bun.');
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
            view('ssh.services.bun.install-bun', [
                'version' => $this->service->version,
            ]),
            'install-bun-'.$this->service->version
        );
        event('service.installed', $this->service);
        $this->service->server->os()->cleanup();
    }

    /**
     * @throws SSHError
     */
    public function uninstall(): void
    {
        $this->service->server->ssh()->exec(
            view('ssh.services.bun.uninstall-bun'),
            'uninstall-bun'
        );
        event('service.uninstalled', $this->service);
        $this->service->server->os()->cleanup();
    }

    public function version(): string
    {
        $version = $this->service->server->ssh()->exec(
            'bun --version'
        );

        return trim($version);
    }
}

