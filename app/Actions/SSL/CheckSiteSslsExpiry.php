<?php

namespace App\Actions\SSL;

use App\Models\Site;
use App\Models\Ssl;
use Illuminate\Support\Facades\Log;
use Throwable;

class CheckSiteSslsExpiry
{
    /**
     * Refresh the stored expiry date for every checkable SSL attached to the
     * site's hosted domains.
     *
     * @return array{checked: int, failed: int}
     */
    public function handle(Site $site): array
    {
        $ssls = $site->hostedDomains()
            ->whereNotNull('ssl_id')
            ->with('ssl')
            ->get()
            ->pluck('ssl')
            ->filter(fn (?Ssl $ssl): bool => $ssl !== null && $ssl->certificate_path !== null)
            ->unique('id');

        if ($ssls->isEmpty()) {
            return ['checked' => 0, 'failed' => 0];
        }

        $ssh = $site->server->ssh();
        $action = app(CheckSslExpiry::class);
        $checked = 0;
        $failed = 0;

        foreach ($ssls as $ssl) {
            try {
                $action->check($ssl, notify: false, ssh: $ssh);
                $checked++;
            } catch (Throwable $e) {
                $failed++;
                Log::warning('[SSL expiry check] Failed to check certificate', [
                    'ssl_id' => $ssl->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return ['checked' => $checked, 'failed' => $failed];
    }
}
