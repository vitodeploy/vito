<?php

namespace App\SiteTypes;

use App\Exceptions\FailedToDeployGitKey;
use App\Exceptions\SSHError;
use App\Models\Site;
use App\Models\SourceControl;
use App\SiteTypes\Concerns\UsesMiseRuntime;
use App\SSH\OS\Composer;
use App\Traits\NormalizesWebDirectory;
use Illuminate\Validation\Rule;

class PHPSite extends AbstractSiteType
{
    use NormalizesWebDirectory;
    use UsesMiseRuntime;

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
            'source_control' => SourceControl::siteValidationRules($this->site->server),
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
            'node_version' => [
                'nullable',
                Rule::in(self::nodeVersionsWithNone()),
            ],
            'bun_version' => [
                'nullable',
                Rule::in(self::bunVersionsWithNone()),
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
            'node_version' => $input['node_version'] ?? 'none',
            'bun_version' => $input['bun_version'] ?? 'none',
        ];
    }

    /**
     * @throws FailedToDeployGitKey
     * @throws SSHError
     */
    public function install(): void
    {
        $this->progress(0, 'isolating-user');
        $this->isolate();
        $this->progress(12, 'installing-node');
        $this->setupNodeIfRequested();
        $this->progress(18, 'installing-bun');
        $this->setupBunIfRequested();
        $this->progress(20, 'creating-vhost');
        $this->site->webserver()->createVHost($this->site);
        $this->progress(25, 'deploying-ssh-key');
        $this->deployKey();
        $this->progress(40, 'cloning-repository');
        $this->cloneRepository();
        $this->progress(60, 'restarting-php');
        $this->site->php()?->restart();
        $this->progress(75, 'installing-composer-dependencies');
        if ($this->site->type_data['composer']) {
            app(Composer::class)->installDependencies($this->site);
        }
        $this->progress(90, 'finishing');
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

    public function vhostData(): array
    {
        return [
            'is_php' => true,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function deploymentEnvironment(): array
    {
        if (! $this->anyRuntimeConfigured()) {
            return [];
        }

        return [
            'PATH' => $this->shimPath(),
        ];
    }

    /**
     * @throws SSHError
     */
    protected function setupNodeIfRequested(): void
    {
        $this->setupRuntimeIfRequested('node');
    }

    /**
     * @throws SSHError
     */
    protected function setupBunIfRequested(): void
    {
        $this->setupRuntimeIfRequested('bun');
    }

    /**
     * @throws SSHError
     */
    private function setupRuntimeIfRequested(string $runtime): void
    {
        $version = $this->site->type_data[$runtime.'_version'] ?? 'none';

        if ($version === 'none' || $version === '') {
            return;
        }

        $existing = Site::existingRuntimeVersionForUser(
            $this->site->server,
            $this->site->user ?? '',
            $runtime,
            $this->site->id,
        );

        if ($existing === $version) {
            return;
        }

        $this->setupMiseRuntime($runtime, $version);
    }

    private function anyRuntimeConfigured(): bool
    {
        foreach (['node_version', 'bun_version'] as $field) {
            $version = $this->site->type_data[$field] ?? 'none';
            if ($version !== 'none' && $version !== '') {
                return true;
            }
        }

        return false;
    }
}
