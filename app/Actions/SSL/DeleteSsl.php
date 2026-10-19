<?php

namespace App\Actions\SSL;

use App\Enums\SslStatus;
use App\Jobs\SSL\DeleteServerSslJob;
use App\Models\ServerLog;
use App\Models\Service;
use App\Models\Ssl;
use App\Services\Webserver\Webserver;
use Illuminate\Validation\ValidationException;

class DeleteSsl
{
    /**
     * @throws ValidationException
     */
    public function delete(Ssl $ssl): void
    {
        $markDeleting = function () use ($ssl): void {
            $ssl->refresh();
            $this->ensureNotInUse($ssl);
            $ssl->status = SslStatus::DELETING;
            $ssl->save();
        };
        $serverId = $ssl->server_id ?? $ssl->site?->server_id;
        try {
            if ($serverId) {
                \Illuminate\Support\Facades\Cache::lock('unique-queue:server-'.$serverId, 600)->block(10, $markDeleting);
            } else {
                $markDeleting();
            }
        } catch (\Illuminate\Contracts\Cache\LockTimeoutException) {
            throw ValidationException::withMessages(['ssl' => 'Another operation on this server is in progress. Please retry.']);
        }

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
        $ssl->site->webserver()->updateVHost($ssl->site);
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

    /**
     * @throws ValidationException
     */
    private function ensureNotInUse(Ssl $ssl): void
    {
        if ($ssl->environmentDomains()->exists()) {
            throw ValidationException::withMessages(['ssl' => 'This certificate is used by environment domains. Remove those references first.']);
        }
        $hostedDomains = $ssl->hostedDomains()->with('site')->get();

        if ($hostedDomains->isEmpty()) {
            return;
        }

        $siteUsages = $hostedDomains->groupBy(fn ($hd) => $hd->site->domain)
            ->map(fn ($domains, $siteDomain) => $siteDomain.' (Domain(s): '.$domains->pluck('domain')->implode(', ').')')
            ->values()
            ->implode(', ');

        throw ValidationException::withMessages([
            'ssl' => 'Currently in use on the following site(s): '.$siteUsages.'. Remove the references to delete this certificate.',
        ]);
    }
}
