<?php

namespace App\Actions\Site;

use App\Models\Site;

class DisableSsl
{
    public function disable(Site $site): void
    {
        $site->ssl_enabled = false;
        $site->save();
        $site->webserver()->updateVHost($site, restart: false);
    }
}
