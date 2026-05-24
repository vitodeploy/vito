<?php

namespace App\Actions\Site;

use App\Models\Site;
use Illuminate\Support\Str;

class EnsureSiteVerificationKey
{
    public function ensure(Site $site): string
    {
        if ($site->verification_key !== null && $site->verification_key !== '') {
            return $site->verification_key;
        }

        $site->verification_key = Str::random(40);
        $site->save();

        return $site->verification_key;
    }
}
