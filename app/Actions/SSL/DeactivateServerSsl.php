<?php

namespace App\Actions\SSL;

// TODO: Remove for prod, used for test only
use App\Enums\SslStatus;
use App\Enums\SslType;
use App\Jobs\SSL\DeactivateServerSslJob;
use App\Models\Server;
use App\Models\ServerLog;
use App\Models\Ssl;

class DeactivateServerSsl
{
    public function deactivate(Server $server, Ssl $ssl): void
    {
        if ($ssl->server_id !== $server->id) {
            abort(404);
        }

        if ($ssl->type !== SslType::CUSTOM->value || $ssl->status !== SslStatus::CREATED) {
            abort(400, 'SSL certificate cannot be deactivated.');
        }

        if (! $ssl->csr_data || ! isset($ssl->csr_data['pk_path'])) {
            abort(400, 'SSL certificate has no CSR data to revert to.');
        }

        $ssl->status = SslStatus::CREATING;
        $ssl->log_id = ServerLog::log($server, 'deactivate-server-ssl', '')->id;
        $ssl->save();

        dispatch(new DeactivateServerSslJob($server, $ssl))->onQueue('ssh');
    }
}
