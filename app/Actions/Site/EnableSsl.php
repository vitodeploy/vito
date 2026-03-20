<?php

namespace App\Actions\Site;

use App\Models\Site;

class EnableSsl
{
    public function enable(Site $site): void
    {
        $site->ssl_enabled = true;
        $site->save();
        $site->webserver()->updateVHost($site, restart: false);
    }
}
