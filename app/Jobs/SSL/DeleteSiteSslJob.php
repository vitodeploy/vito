<?php

namespace App\Jobs\SSL;

use App\Models\Server;
use App\Models\Ssl;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeleteSiteSslJob implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Server $server, protected Ssl $ssl) {}

    public function handle(): void
    {
        $this->server->ssh()->exec(
            view('ssh.ssl.delete-ssl', [
                'sslId' => $this->ssl->id,
                'isWildcard' => $this->ssl->is_wildcard,
            ]),
            'delete-site-ssl',
        );

        $this->ssl->delete();
    }

    public function failed(Exception $e): void
    {
        $this->ssl->delete();
    }
}
