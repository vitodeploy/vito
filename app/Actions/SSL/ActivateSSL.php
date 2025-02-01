<?php

namespace App\Actions\SSL;

use App\Models\Ssl;

class ActivateSSL
{
    public function activate(Ssl $ssl): void
    {
        $ssl->site->ssls()->update(['is_active' => false]);
        $ssl->is_active = true;
        $ssl->save();
        $ssl->site->webserver()->updateVHost($ssl->site);
    }
}
