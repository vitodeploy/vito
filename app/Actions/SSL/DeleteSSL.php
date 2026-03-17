<?php

namespace App\Actions\SSL;

use App\Enums\SslStatus;
use App\Models\Service;
use App\Models\Ssl;
use App\Services\Webserver\Webserver;

class DeleteSSL
{
    public function delete(Ssl $ssl): void
    {
        $ssl->status = SslStatus::DELETING;
        $ssl->save();

        $parent = $ssl->parent();

        /** @var Service $service */
        $service = $parent->server->webserver();
        /** @var Webserver $webserver */
        $webserver = $service->handler();
        $webserver->removeSSL($ssl);
        $ssl->delete();

        $parent->refreshVhost();
    }
}
