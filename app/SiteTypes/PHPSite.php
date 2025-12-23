<?php

namespace App\SiteTypes;

use App\Exceptions\FailedToDeployGitKey;
use App\Exceptions\SSHError;
use App\Models\Site;
use App\SSH\OS\Composer;
use App\SSH\OS\Git;
use App\Traits\NormalizesWebDirectory;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;

class PHPSite extends AbstractSiteType
{
    use NormalizesWebDirectory;

    public static function id(): string
    {
        return 'php';
    }

    public function language(): string
    {
        return 'php';
    }

    public function requiredServices(): array
    {
        return [
            'php',
            'webserver',
        ];
    }

    public static function make(): self
    {
        return new self(new Site(['type' => self::id()]));
    }

    public function createRules(array $input): array
    {
        return [
            'php_version' => [
                'required',
                Rule::in($this->site->server->installedPHPVersions()),
            ],
            'source_control' => [
                'required',
                Rule::exists('source_controls', 'id'),
            ],
            'web_directory' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9._\-\/]+$/',
                'not_regex:/\.\./',
            ],
            'repository' => [
                'required',
            ],
            'branch' => [
                'required',
            ],
            'composer' => [
                'nullable',
            ],
        ];
    }

    public function createFields(array $input): array
    {
        return [
            'web_directory' => $this->normalizeWebDirectory($input['web_directory'] ?? ''),
            'source_control_id' => $input['source_control'] ?? '',
            'repository' => $input['repository'] ?? '',
            'branch' => $input['branch'] ?? '',
            'php_version' => $input['php_version'] ?? '',
            'composer' => $input['composer'] ?? '',
        ];
    }

    public function data(array $input): array
    {
        return [
            'composer' => isset($input['composer']) && $input['composer'],
        ];
    }

    /**
     * @throws FailedToDeployGitKey
     * @throws SSHError
     */
    public function install(): void
    {
        $this->isolate();
        $this->site->webserver()->createVHost($this->site);
        $this->progress(15);
        $this->deployKey();
        $this->progress(30);
        app(Git::class)->clone($this->site);
        $this->progress(65);
        $this->site->php()?->restart();
        if ($this->site->type_data['composer']) {
            app(Composer::class)->installDependencies($this->site);
        }
    }

    public function baseCommands(): array
    {
        return [
            [
                'name' => 'composer:install',
                'command' => 'composer install --no-dev --no-interaction --no-progress',
            ],
        ];
    }

    public function vhost(string $webserver): string|View
    {
        if ($webserver === 'nginx') {
            return view('ssh.services.webserver.nginx.vhost', [
                'header' => [
                    view('ssh.services.webserver.nginx.vhost-blocks.force-ssl', ['site' => $this->site]),
                ],
                'main' => [
                    view('ssh.services.webserver.nginx.vhost-blocks.port', ['site' => $this->site]),
                    view('ssh.services.webserver.nginx.vhost-blocks.core', ['site' => $this->site]),
                    view('ssh.services.webserver.nginx.vhost-blocks.php', ['site' => $this->site]),
                    view('ssh.services.webserver.nginx.vhost-blocks.redirects', ['site' => $this->site]),
                ],
            ]);
        }

        if ($webserver === 'caddy') {
            return view('ssh.services.webserver.caddy.vhost', [
                'site' => $this->site,
                'main' => [
                    view('ssh.services.webserver.caddy.vhost-blocks.force-ssl', ['site' => $this->site]),
                    view('ssh.services.webserver.caddy.vhost-blocks.port', ['site' => $this->site]),
                    view('ssh.services.webserver.caddy.vhost-blocks.core', ['site' => $this->site]),
                    view('ssh.services.webserver.caddy.vhost-blocks.php', ['site' => $this->site]),
                    view('ssh.services.webserver.caddy.vhost-blocks.redirects', ['site' => $this->site]),
                ],
            ]);
        }

        return '';
    }
}
