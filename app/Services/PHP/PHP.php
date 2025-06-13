<?php

namespace App\Services\PHP;

use App\Exceptions\SSHCommandError;
use App\Exceptions\SSHError;
use App\Services\AbstractService;
use Closure;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PHP extends AbstractService
{
    public static function id(): string
    {
        return 'php';
    }

    public static function type(): string
    {
        return 'php';
    }

    public function unit(): string
    {
        return 'php'.$this->service->version.'-fpm';
    }

    public function creationRules(array $input): array
    {
        return [
            'version' => [
                'required',
                Rule::in(config('service.services.php.versions')),
                Rule::unique('services', 'version')
                    ->where('type', 'php')
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
                        ->where('php_version', $this->service->version)
                        ->exists();
                    if ($hasSite) {
                        $fail('Some sites are using this PHP version.');
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
            view('ssh.services.php.install-php', [
                'version' => $this->service->version,
                'user' => $server->getSshUser(),
            ]),
            'install-php-'.$this->service->version
        );
        $this->installComposer();
        $this->service->server->os()->cleanup();
    }

    /**
     * @throws SSHError
     */
    public function uninstall(): void
    {
        $this->service->server->ssh()->exec(
            view('ssh.services.php.uninstall-php', [
                'version' => $this->service->version,
            ]),
            'uninstall-php-'.$this->service->version
        );
        $this->service->server->os()->cleanup();
    }

    /**
     * @throws SSHError
     */
    public function setDefaultCli(): void
    {
        $this->service->server->ssh()->exec(
            view('ssh.services.php.change-default-php', [
                'version' => $this->service->version,
            ]),
            'change-default-php'
        );
    }

    /**
     * @throws SSHError
     */
    public function installExtension(string $name): void
    {
        $result = $this->service->server->ssh()->exec(
            view('ssh.services.php.install-php-extension', [
                'version' => $this->service->version,
                'name' => $name,
            ]),
            'install-php-extension-'.$name
        );
        $pos = strpos($result, '[PHP Modules]');
        if ($pos === false) {
            throw new SSHCommandError('Failed to install extension');
        }
        $result = Str::substr($result, $pos);
        if (! Str::contains($result, $name)) {
            throw new SSHCommandError('Failed to install extension');
        }
    }

    /**
     * @throws SSHError
     */
    public function installComposer(): void
    {
        $this->service->server->ssh()->exec(
            view('ssh.services.php.install-composer'),
            'install-composer'
        );
    }

    /**
     * @throws SSHError
     */
    public function getPHPIni(string $type): string
    {
        return $this->service->server->os()->readFile(
            sprintf('/etc/php/%s/%s/php.ini', $this->service->version, $type)
        );
    }

    /**
     * @throws SSHError
     */
    public function createFpmPool(string $user, string $version): void
    {
        $this->service->server->ssh()->write(
            "/etc/php/{$version}/fpm/pool.d/{$user}.conf",
            view('ssh.services.php.fpm-pool', [
                'user' => $user,
                'version' => $version,
            ]),
            'root'
        );

        $this->service->server->systemd()->restart($this->unit());
    }

    /**
     * @throws SSHError
     */
    public function removeFpmPool(string $user, string $version, ?int $siteId): void
    {
        $this->service->server->ssh()->exec(
            view('ssh.services.php.remove-fpm-pool', [
                'user' => $user,
                'version' => $version,
            ]),
            "remove-{$version}fpm-pool-{$user}",
            $siteId
        );
    }
}
