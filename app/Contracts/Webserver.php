<?php

namespace App\Contracts;

use App\Models\Site;
use App\Models\Ssl;

interface Webserver
{
    public function createVHost(Site $site): void;

    public function updateVHost(Site $site): void;

    public function deleteSite(Site $site): void;

    public function changePHPVersion(Site $site, string $version): void;

    public function setupSSL(Ssl $ssl): void;

    public function removeSSL(Ssl $ssl): void;

    public function updateRedirects(Site $site, array $redirects): void;
}
