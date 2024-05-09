<?php

namespace App\SiteTypes;

use App\Enums\SiteFeature;
use App\Exceptions\SourceControlIsNotConnected;
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
            SiteFeature::ENV,
            SiteFeature::SSL,
            SiteFeature::QUEUES,
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
            'repository' => [
                'required',
            ],
            'branch' => [
                'required',
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
        ];
    }

    public function data(array $input): array
    {
        return [
            'composer' => isset($input['composer']) && $input['composer'],
        ];
    }

    /**
     * @throws SourceControlIsNotConnected
     */
    public function install(): void
    {
        /** @var Webserver $webserver */
        $webserver = $this->site->server->webserver()->handler();
        $webserver->createVHost($this->site);
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
}
