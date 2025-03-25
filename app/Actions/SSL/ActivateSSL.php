<?php

namespace App\Actions\SSL;

use App\Models\Ssl;

class ActivateSSL
{
    public function activate(Ssl $ssl): void
    {
        $ssl->site->resetSslDomains($ssl->getDomains());

        $ssl->is_active = true;
        $ssl->save();
        $ssl->site->webserver()->updateVHost($ssl->site);
    }
}
