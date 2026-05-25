<?php

namespace App\Actions\Site;

use App\Models\Site;
use Illuminate\Validation\ValidationException;

class DisableSsl
{
    public function disable(Site $site): void
    {
        if (! $site->webserver()->canConfigureSSL()) {
            throw ValidationException::withMessages([
                'ssl_enabled' => 'SSL cannot be changed for this webserver.',
            ]);
        }

        $site->ssl_enabled = false;
        $site->save();
        $site->webserver()->updateVHost($site);

        app(BroadcastSiteUpdate::class)->broadcast($site);
    }
}
