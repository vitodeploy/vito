<?php

namespace App\Actions\Site;

use App\Models\Site;
use Illuminate\Validation\ValidationException;

class EnableSsl
{
    public function enable(Site $site): void
    {
        if (! $site->webserver()->canConfigureSSL()) {
            throw ValidationException::withMessages([
                'ssl_enabled' => 'SSL cannot be changed for this webserver.',
            ]);
        }

        $site->ssl_enabled = true;
        $site->save();
        $site->webserver()->updateVHost($site);
    }
}
