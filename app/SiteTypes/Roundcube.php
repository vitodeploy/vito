<?php

namespace App\SiteTypes;

use App\Actions\Database\CreateDatabase;
use App\Actions\Database\CreateDatabaseUser;
use App\Actions\Database\LinkUser;
use App\Enums\SiteFeature;
use App\Models\Database;
use App\Models\DatabaseUser;
use Closure;
use Illuminate\Validation\Rule;

class Roundcube extends AbstractSiteType
{
    public function language(): string
    {
        return 'php';
    }

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
            'version' => 'required|string',
            'imap_host' => 'required|string',
            'smtp_host' => 'required|string',
            'support_url' => 'sometimes',
        ];
    }

    public function createFields(array $input): array
    {
        return [
            'web_directory' => 'public_html',
            'php_version' => $input['php_version'],
        ];
    }

    public function data(array $input): array
    {
        return [
            'version' => $input['version'],
            'url' => $this->site->getUrl(),
            'imap_host' => $input['imap_host'],
            'smtp_host' => $input['smtp_host'],
            'support_url' => $input['support_url'],
        ];
    }

    public function install(): void
    {
        $this->site->server->webserver()->handler()->createVHost($this->site);
        $this->progress(60);
        app(\App\SSH\Roundcube\Roundcube::class)->install($this->site);
    }

    public function editRules(array $input): array
    {
        return [
        ];
    }

    public function edit(): void
    {
        //
    }
}
