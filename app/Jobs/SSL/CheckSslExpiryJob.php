<?php

namespace App\Jobs\SSL;

use App\Actions\SSL\CheckSslExpiry;
use App\Enums\SslStatus;
use App\Enums\SslType;
use App\Models\Server;
use App\Models\Ssl;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class CheckSslExpiryJob implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Server $server) {}

    public function handle(): void
    {
        $ssls = Ssl::query()
            ->with('site.server')
            ->whereHas('site', fn ($q) => $q->where('server_id', $this->server->id))
            ->whereNotNull('site_id')
            ->where('type', SslType::LETSENCRYPT)
            ->where('status', SslStatus::CREATED)
            ->whereNotNull('certificate_path')
            ->get();

        if ($ssls->isEmpty()) {
            return;
        }

        $ssh = $this->server->ssh();
        $action = app(CheckSslExpiry::class);

        foreach ($ssls as $ssl) {
            try {
                $action->check($ssl, notify: true, ssh: $ssh);
            } catch (Throwable $e) {
                Log::warning('[SSL expiry check] Failed to check certificate', [
                    'ssl_id' => $ssl->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
