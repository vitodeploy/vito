<?php

namespace App\SiteTypes;

use App\Enums\SiteFeature;
use App\SSH\Services\Webserver\Webserver;

class ReverseProxy extends AbstractSiteType
{
    public function language(): string
    {
        return 'reverse-proxy';
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
            'port' => 'required|integer',
            'auto-installed' => 'sometimes',
        ];
    }

    public function createFields(array $input): array
    {
        return [
            'web_directory' => 'public_html',
        ];
    }

    public function data(array $input): array
    {
        return [
            'url' => $this->site->getUrl(),
            'port' => $input['port'],
            'auto-installed' => $input['auto-installed'] ?? null,
        ];
    }

    public function install(): void
    {
        /** @var Webserver $webserver */
        $webserver = $this->site->server->webserver()->handler();
        $webserver->createVHost($this->site);
        $this->progress(65);
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
