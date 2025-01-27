<?php

namespace App\SiteTypes;

use App\Enums\SiteFeature;
use App\Exceptions\SSHError;
use App\SSH\Services\Webserver\Webserver;
use Illuminate\Validation\Rule;

class PHPMyAdmin extends PHPSite
{
    public function supportedFeatures(): array
    {
        return [
            SiteFeature::SSL,
        ];
    }

    public function createRules(array $input): array
    {
        return [
            'php_version' => [
                'required',
                Rule::in($this->site->server->installedPHPVersions()),
            ],
            'version' => 'required',
        ];
    }

    public function createFields(array $input): array
    {
        return [
            'web_directory' => '',
            'php_version' => $input['php_version'] ?? '',
        ];
    }

    public function data(array $input): array
    {
        return [
            'version' => $input['version'],
        ];
    }

    /**
     * @throws SSHError
     */
    public function install(): void
    {
        $this->isolate();

        /** @var Webserver $webserver */
        $webserver = $this->site->server->webserver()->handler();
        $webserver->createVHost($this->site);
        $this->progress(30);
        app(\App\SSH\PHPMyAdmin\PHPMyAdmin::class)->install($this->site);
        $this->progress(65);
        $this->site->php()?->restart();
    }
}
