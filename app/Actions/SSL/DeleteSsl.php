<?php

namespace App\Actions\SSL;

use App\Enums\SslStatus;
use App\Jobs\SSL\DeleteServerSslJob;
use App\Models\ServerLog;
use App\Models\Service;
use App\Models\Ssl;
use App\Services\Webserver\Webserver;

class DeleteSsl
{
    public function delete(Ssl $ssl): void
    {
        $ssl->status = SslStatus::DELETING;
        $ssl->save();

        if ($ssl->site_id === null) {
            $this->deleteServerSsl($ssl);

            return;
        }

        /** @var Service $service */
        $service = $ssl->site->server->webserver();
        /** @var Webserver $webserver */
        $webserver = $service->handler();
        $webserver->removeSSL($ssl);
        $ssl->delete();
        $ssl->site->webserver()->updateVHost($ssl->site, regenerate: [
            'port',
        ]);
    }

    private function deleteServerSsl(Ssl $ssl): void
    {
        $server = $ssl->server;

        if (! $server) {
            $ssl->delete();

            return;
        }

        $ssl->log_id = ServerLog::log($server, 'delete-server-ssl', '')->id;
        $ssl->save();

        dispatch(new DeleteServerSslJob($server, $ssl))->onQueue('ssh');
    }
}
