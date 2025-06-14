<?php

namespace App\Services\Webserver;

use App\Models\Site;
use App\Models\Ssl;
use App\Services\ServiceInterface;

interface Webserver extends ServiceInterface
{
    public function createVHost(Site $site): void;

    /**
     * @param  array<string, string>  $replace  replace blocks
     * @param  array<int, string>  $regenerate  regenerates the blocks
     * @param  array<string, string>  $append  appends to the blocks
     */
    public function updateVHost(Site $site, ?string $vhost = null, array $replace = [], array $regenerate = [], array $append = []): void;

    public function getVHost(Site $site): string;

    public function deleteSite(Site $site): void;

    public function changePHPVersion(Site $site, string $version): void;

    public function setupSSL(Ssl $ssl): void;

    public function removeSSL(Ssl $ssl): void;
}
