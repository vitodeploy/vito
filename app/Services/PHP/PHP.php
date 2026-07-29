<?php

namespace App\Services\PHP;

use App\DTOs\ServiceLog;
use App\Exceptions\SSHCommandError;
use App\Exceptions\SSHError;
use App\Services\AbstractService;
use App\Services\HasLogs;
use Closure;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PHP extends AbstractService implements HasLogs
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
        $server->ssh()
            ->setLog($this->service->log)
            ->exec(
                view('ssh.services.php.install-php', [
                    'version' => $this->service->version,
                    'user' => $server->getSshUser(),
                ]),
                'install-php-'.$this->service->version
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
            view('ssh.services.php.uninstall-php', [
                'version' => $this->service->version,
            ]),
            'uninstall-php-'.$this->service->version
        );
        event('service.uninstalled', $this->service);
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

    public function versionCommand(): ?string
    {
        return sprintf(
            '/usr/bin/php%s -r %s 2>/dev/null',
            escapeshellarg($this->service->version),
            escapeshellarg('echo PHP_VERSION;')
        );
    }

    public function parseVersionOutput(string $output): ?string
    {
        if (preg_match('/(\d+\.\d+\.\d+)/', $output, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public function logs(): array
    {
        $version = $this->service->version;
        $serviceLabel = 'PHP '.$version;

        $logs = [
            new ServiceLog(
                key: 'php:'.$version.':fpm-journal',
                serviceLabel: $serviceLabel,
                label: 'FPM service journal',
                source: ServiceLog::SOURCE_JOURNAL,
                target: 'php'.$version.'-fpm.service',
            ),
        ];

        $sites = $this->service->server->relationLoaded('sites')
            ? $this->service->server->sites
                ->where('php_version', $version)
                ->sortBy('id')
            : $this->service->server->sites()
                ->where('php_version', $version)
                ->orderBy('id')
                ->get(['id', 'domain', 'user']);

        /** @var array<string, array<int, string>> $domainsByUser */
        $domainsByUser = [];
        foreach ($sites as $site) {
            $user = $site->user;
            $domainsByUser[$user] = $domainsByUser[$user] ?? [];
            $domainsByUser[$user][] = $site->domain;
        }

        foreach ($domainsByUser as $user => $domains) {
            $logs[] = new ServiceLog(
                key: 'php:'.$version.':user:'.$user,
                serviceLabel: $serviceLabel,
                label: 'FPM pool '.$user.' ('.implode(', ', $domains).')',
                source: ServiceLog::SOURCE_FILE,
                target: '/home/'.$user.'/.logs/php_errors.log',
            );
        }

        return $logs;
    }
}
