<?php

namespace App\Actions\SSL;

use App\Models\Ssl;

class DeactivateSSL
{
    public function deactivate(Ssl $ssl): void
    {
        $ssl->is_active = false;
        $ssl->save();
        $ssl->site->webserver()->updateVHost($ssl->site, regenerate: [
            'port',
        ]);
    }
}
