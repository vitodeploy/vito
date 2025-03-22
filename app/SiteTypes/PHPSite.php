<?php

namespace App\SiteTypes;

use App\Enums\SiteFeature;
use App\Exceptions\FailedToDeployGitKey;
use App\Exceptions\SSHError;
use App\Models\Site;
use App\SSH\Composer\Composer;
use App\SSH\Git\Git;
use Illuminate\Validation\Rule;

class PHPSite extends AbstractSiteType
{
    public function language(): string
    {
        return 'php';
    }

    public function supportedFeatures(): array
    {
        return [
            SiteFeature::DEPLOYMENT,
            SiteFeature::COMMANDS,
            SiteFeature::ENV,
            SiteFeature::SSL,
            SiteFeature::WORKERS,
            SiteFeature::DUPLICATION,
        ];
    }

    public function baseCommands(): array
    {
        return [
            [
                'name' => 'Install Composer Dependencies',
                'command' => 'composer install --no-dev --no-interaction --no-progress',
            ],
        ];
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
            'web_directory' => $input['web_directory'] ?? '',
            'source_control_id' => $input['source_control'] ?? '',
            'repository' => $input['repository'] ?? '',
            'branch' => $input['branch'] ?? '',
            'php_version' => $input['php_version'] ?? '',
            'composer' => $input['php_version'] ?? '',
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

    public function editRules(array $input): array
    {
        return [];
    }

    public function edit(): void
    {
        //
    }

    /**
     * @throws FailedToDeployGitKey
     * @throws SSHError
     */
    public function duplicateSite(): void
    {
        $this->site->webserver()->createVHost($this->site);
        $this->progress(15);
        $this->deployKey();
        $this->progress(30);
        $sourceSite = Site::query()->findOrFail($this->site->type_data['copied_from_site_id']);
        $this->progress(40);
        $this->site->webserver()->duplicateSite($sourceSite, $this->site);
        if ($this->site->sourceControl) {
            app(Git::class)->fetchOrigin($this->site);
            app(Git::class)->checkout($this->site);
        }
        $this->progress(65);
        $this->site->php()?->restart();
    }
}
