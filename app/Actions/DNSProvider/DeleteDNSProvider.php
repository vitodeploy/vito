<?php

namespace App\Actions\DNSProvider;

use App\Models\DNSProvider;

class DeleteDNSProvider
{
    public function delete(DNSProvider $dnsProvider): void
    {
        $dnsProvider->delete();
    }
}
