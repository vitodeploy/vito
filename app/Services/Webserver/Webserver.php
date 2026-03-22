<?php

namespace App\Services\Webserver;

use App\Enums\SslMethod;
use App\Models\Site;
use App\Models\Ssl;
use App\Services\ServiceInterface;

interface Webserver extends ServiceInterface
{
    public function generateVhost(Site $site, ?string $template = null): string;

    public function createVHost(Site $site): void;

    public function updateVHost(Site $site, ?string $vhost = null, bool $restart = false): void;

    public function getVHost(Site $site): string;

    public function deleteSite(Site $site): void;

    public function setupSSL(Ssl $ssl): void;

    public function removeSSL(Ssl $ssl): void;

    public function createsSiteSSLs(): bool;

    /** @return array<string, mixed> */
    public function siteDefaults(): array;

    public function canConfigureSSL(): bool;

    /** @return array<string>|null */
    public function allowedSslMethods(): ?array;

    public function defaultSslMethod(): SslMethod;
}
