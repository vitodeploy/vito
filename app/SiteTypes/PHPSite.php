<?php

namespace App\SiteTypes;

use App\Exceptions\FailedToDeployGitKey;
use App\Exceptions\SSHError;
use App\Models\Site;
use App\Models\SourceControl;
use App\SSH\OS\Composer;
use App\Tooling\ToolingRegistry;
use App\Traits\NormalizesWebDirectory;
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

    public static function createTimeTools(): array
    {
        return ['node', 'bun'];
    }

    public static function supportsTooling(): bool
    {
        return true;
    }

    public function createRules(array $input): array
    {
        $rules = [
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
        ];

        foreach (static::createTimeTools() as $toolId) {
            $tool = ToolingRegistry::find($toolId);
            if (! $tool) {
                continue;
            }
            $rules[$tool::typeDataKey()] = [
                'nullable',
                Rule::in($tool::supportedVersionsWithNone()),
            ];
        }

        return $rules;
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
        $data = [
            'composer' => isset($input['composer']) && $input['composer'],
        ];

        foreach (static::createTimeTools() as $toolId) {
            $tool = ToolingRegistry::find($toolId);
            if (! $tool) {
                continue;
            }
            $key = $tool::typeDataKey();
            $data[$key] = $input[$key] ?? 'none';
        }

        return $data;
    }

    /**
     * @throws FailedToDeployGitKey
     * @throws SSHError
     */
    public function install(): void
    {
        $this->progress(0, 'isolating-user');
        $this->isolate();
        $this->progress(15, 'installing-tooling');
        $this->setupRequestedTooling();
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
}
