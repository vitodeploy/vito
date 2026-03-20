<?php

namespace App\Actions\SSL;

use App\Enums\SslStatus;
use App\Enums\SslType;
use App\Jobs\SSL\DeactivateServerSslJob;
use App\Models\Server;
use App\Models\ServerLog;
use App\Models\Ssl;
use Illuminate\Validation\ValidationException;

class DeactivateServerSsl
{
    /**
     * @throws ValidationException
     */
    public function deactivate(Server $server, Ssl $ssl): void
    {
        if ($ssl->type !== SslType::CUSTOM->value || $ssl->status !== SslStatus::CREATED) {
            throw ValidationException::withMessages([
                'ssl' => 'SSL certificate cannot be deactivated.',
            ]);
        }

        if (! $ssl->csr_data || ! isset($ssl->csr_data['pk_path'])) {
            throw ValidationException::withMessages([
                'ssl' => 'SSL certificate has no CSR data to revert to.',
            ]);
        }

        $ssl->status = SslStatus::CREATING;
        $ssl->log_id = ServerLog::log($server, 'deactivate-server-ssl', '')->id;
        $ssl->save();

        dispatch(new DeactivateServerSslJob($server, $ssl))->onQueue('ssh');
    }
}
