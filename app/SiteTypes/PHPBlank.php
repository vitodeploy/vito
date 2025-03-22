<?php

namespace App\SiteTypes;

use App\Enums\SiteFeature;
use App\Exceptions\SSHError;
use App\Models\Site;
use Illuminate\Validation\Rule;

class PHPBlank extends PHPSite
{
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

    public function createRules(array $input): array
    {
        return [
            'php_version' => [
                'required',
                Rule::in($this->site->server->installedPHPVersions()),
            ],
        ];
    }

    public function createFields(array $input): array
    {
        return [
            'web_directory' => $input['web_directory'] ?? '',
            'php_version' => $input['php_version'] ?? '',
        ];
    }

    public function data(array $input): array
    {
        return [];
    }

    /**
     * @throws SSHError
     */
    public function install(): void
    {
        $this->isolate();
        $this->site->webserver()->createVHost($this->site);
        $this->progress(65);
        $this->site->php()?->restart();
    }

    public function baseCommands(): array
    {
        return [];
    }

    /**
     * @throws SSHError
     */
    public function duplicateSite(): void
    {
        $this->site->webserver()->createVHost($this->site);
        $sourceSite = Site::query()->findOrFail($this->site->type_data['copied_from_site_id']);
        $this->progress(35);
        $this->site->webserver()->duplicateSite($sourceSite, $this->site);
        $this->progress(65);
        $this->site->php()?->restart();
    }
}
