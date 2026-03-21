<?php

namespace App\Jobs\SSL;

use App\Models\Server;
use App\Models\Ssl;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class DeleteSiteSslJob implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Server $server, protected Ssl $ssl) {}

    public function handle(): void
    {
        $result = $this->server->ssh()->exec(
            view('ssh.ssl.delete-ssl', [
                'sslId' => $this->ssl->id,
                'isWildcard' => $this->ssl->is_wildcard,
            ]),
            'delete-site-ssl',
        );

        if (Str::contains($result, 'SSL DELETED SUCCESSFULLY')) {
            $this->ssl->delete();
        }
    }
}
