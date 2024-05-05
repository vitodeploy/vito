<?php

namespace App\SiteTypes;

use App\Enums\SiteFeature;
use App\Exceptions\SourceControlIsNotConnected;
use App\SSH\Git\Git;
use Illuminate\Validation\Rule;

class Nextjs extends AbstractSiteType
{
    public function language(): string
    {
        return 'javascript';
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
        ];
    }

    public function data(array $input): array
    {
        return [];
    }

    /**
     * @throws SourceControlIsNotConnected
     */
    public function install(): void
    {
        $this->site->server->webserver()->handler()->createVHost($this->site);
        $this->progress(15);
        $this->deployKey();
        $this->progress(30);
        app(Git::class)->clone($this->site);
        $this->progress(65);
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
