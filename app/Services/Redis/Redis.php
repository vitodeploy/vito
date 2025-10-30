<?php

namespace App\Services\Redis;

use App\Exceptions\ServiceInstallationFailed;
use App\Exceptions\SSHError;
use App\Services\AbstractService;
use Closure;

class Redis extends AbstractService
{
    public static function id(): string
    {
        return 'redis';
    }

    public static function type(): string
    {
        return 'memory_database';
    }

    public function unit(): string
    {
        return 'redis';
    }

    public function creationRules(array $input): array
    {
        return [
            'type' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $redisExists = $this->service->server->memoryDatabase();
                    if ($redisExists) {
                        $fail('You already have a Redis service on the server.');
                    }
                },
            ],
        ];
    }

    /**
     * @throws ServiceInstallationFailed
     * @throws SSHError
     */
    public function install(): void
    {
        $this->service->server->ssh()->exec(
            view('ssh.services.redis.install'),
            'install-redis'
        );
        $status = $this->service->server->systemd()->status($this->unit());
        $this->service->validateInstall($status);
        event('service.installed', $this->service);
        $this->service->server->os()->cleanup();
    }

    /**
     * @throws SSHError
     */
    public function uninstall(): void
    {
        $this->service->server->ssh()->exec(
            view('ssh.services.redis.uninstall'),
            'uninstall-redis'
        );
        event('service.uninstalled', $this->service);
        $this->service->server->os()->cleanup();
    }

    public function version(): string
    {
        return $this->service->server->ssh()->exec('redis-server --version | awk \'{print $3}\' | cut -d= -f2', 'get-redis-version');
    }
}
