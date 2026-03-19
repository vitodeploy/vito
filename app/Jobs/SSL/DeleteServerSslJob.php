<?php

namespace App\Jobs\SSL;

use App\Enums\SslStatus;
use App\Models\Server;
use App\Models\ServerLog;
use App\Models\Ssl;
use App\Traits\BroadcastsSslEvents;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class DeleteServerSslJob implements ShouldQueue
{
    use BroadcastsSslEvents;
    use Queueable;
    use UniqueQueue;

    public function __construct(protected Server $server, protected Ssl $ssl) {}

    public function handle(): void
    {
        $this->run("server-{$this->server->id}", function () {
            $ssh = $this->server->ssh()->setLog($this->ssl->log);

            $result = $ssh->exec(view('ssh.ssl.delete-ssl', [
                'sslId' => $this->ssl->id,
                'isWildcard' => $this->ssl->is_wildcard,
            ]));

            if (! Str::contains($result, 'SSL_DELETED_SUCCESSFULLY')) {
                throw new Exception('SSL deletion failed: '.$result);
            }

            // TODO: Regenerate Site SSL

            $this->broadcastSslEvent($this->ssl, $this->server->project_id, 'ssl.deleted');

            $this->ssl->delete();
        });
    }

    public function failed(Exception $e): void
    {
        $this->ssl->status = SslStatus::FAILED;
        $this->ssl->save();

        $this->broadcastSslEvent($this->ssl, $this->server->project_id);

        ServerLog::log(
            $this->server,
            'delete-server-ssl-failed',
            $e->getMessage(),
        );
    }
}
