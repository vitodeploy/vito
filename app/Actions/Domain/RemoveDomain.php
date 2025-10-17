<?php

namespace App\Actions\Domain;

use App\Models\Domain;

class RemoveDomain
{
    public function remove(Domain $domain): void
    {
        $domain->delete();
    }
}
