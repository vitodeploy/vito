<?php

namespace App\Actions\SSL;

use App\Enums\SslStatus;
use App\Models\Ssl;
use App\SSH\Services\Webserver\Webserver;

class DeleteSSL
{
    public function delete(Ssl $ssl): void
    {
        $ssl->status = SslStatus::DELETING;
        $ssl->save();
        $service = $ssl->site->server->webserver();
        assert($service !== null);
        /** @var Webserver $webserver */
        $webserver = $service->handler();
        $webserver->removeSSL($ssl);
        $ssl->delete();
    }
}
