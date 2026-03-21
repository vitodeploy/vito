<?php

namespace App\Actions\Domain;

use App\Models\Domain;
use Illuminate\Validation\ValidationException;

class RemoveDomain
{
    /**
     * @throws ValidationException
     */
    public function remove(Domain $domain): void
    {
        $sslCount = $domain->ssls()->count();

        if ($sslCount > 0) {
            throw ValidationException::withMessages([
                'domain' => 'This domain is linked to '.$sslCount.' SSL '.($sslCount === 1 ? 'certificate' : 'certificates').' used for auto-renewal. Remove the associated SSL certificate(s) before deleting this domain.',
            ]);
        }

        $domain->delete();
    }
}
