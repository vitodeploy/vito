<?php

namespace App\Actions\ApiKey;

use App\Models\ApiKey;

class RegenerateAPiKey
{
    public function regenerate(ApiKey $apiKey)
    {
        return $apiKey->regenerate();
    }
}
