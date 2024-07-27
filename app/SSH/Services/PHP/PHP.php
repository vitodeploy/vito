<?php

namespace App\SSH\Services\PHP;

use App\Exceptions\SSHCommandError;
use App\SSH\HasScripts;
use App\SSH\Services\AbstractService;
use Closure;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PHP extends AbstractService
{
    use HasScripts;

    public function creationRules(array $input): array
    {
        return [
            'version' => [
                'required',
                Rule::in(config('core.php_versions')),
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
                function (string $attribute, mixed $value, Closure $fail) {
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

    public function install(): void
    {
        $server = $this->service->server;
        $server->ssh()->exec(
            $this->getScript('install-php.sh', [
                'version' => $this->service->version,
                'user' => $server->getSshUser(),
            ]),
            'install-php-'.$this->service->version
        );
        $this->service->server->os()->cleanup();
    }

    public function uninstall(): void
    {
        $this->service->server->ssh()->exec(
            $this->getScript('uninstall-php.sh', [
                'version' => $this->service->version,
            ]),
            'uninstall-php-'.$this->service->version
        );
        $this->service->server->os()->cleanup();
    }

    public function setDefaultCli(): void
    {
        $this->service->server->ssh()->exec(
            $this->getScript('change-default-php.sh', [
                'version' => $this->service->version,
            ]),
            'change-default-php'
        );
    }

    /**
     * @throws SSHCommandError
     */
    public function installExtension($name): void
    {
        $result = $this->service->server->ssh()->exec(
            $this->getScript('install-php-extension.sh', [
                'version' => $this->service->version,
                'name' => $name,
            ]),
            'install-php-extension-'.$name
        );
        $result = Str::substr($result, strpos($result, '[PHP Modules]'));
        if (! Str::contains($result, $name)) {
            throw new SSHCommandError('Failed to install extension');
        }
    }

    public function installComposer(): void
    {
        $this->service->server->ssh()->exec(
            $this->getScript('install-composer.sh'),
            'install-composer'
        );
    }

    public function getPHPIni(string $type): string
    {
        return $this->service->server->os()->readFile(
            sprintf('/etc/php/%s/%s/php.ini', $this->service->version, $type)
        );
    }
}
