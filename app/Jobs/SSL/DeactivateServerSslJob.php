<?php

namespace App\Jobs\SSL;

// TODO: Remove for prod, used for test only
use App\Enums\SslStatus;
use App\Models\Server;
use App\Models\Ssl;
use App\Traits\BroadcastsSslEvents;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class DeactivateServerSslJob implements ShouldQueue
{
    use BroadcastsSslEvents;
    use Queueable;
    use UniqueQueue;

    public function __construct(protected Server $server, protected Ssl $ssl) {}

    public function handle(): void
    {
        $this->run("server-{$this->server->id}", function () {
            $ssh = $this->server->ssh()->setLog($this->ssl->log);

            $basePath = '/etc/ssl/vito/'.$this->ssl->id;
            $pkPath = $this->ssl->csr_data['pk_path'];

            $result = $ssh->exec(view('ssh.ssl.deactivate-ssl', [
                'basePath' => $basePath,
                'passphrase' => $this->ssl->csr_passphrase,
                'pkPath' => $pkPath,
                'encryptedPath' => $basePath.'/private.encrypted.key',
            ]));

            if (! Str::contains($result, 'SSL DEACTIVATED SUCCESSFULLY')) {
                throw new Exception('SSL deactivation failed: '.$result);
            }

            $this->ssl->certificate = null;
            $this->ssl->ca = null;
            $this->ssl->expires_at = null;
            $this->ssl->certificate_path = null;
            $this->ssl->pk_path = null;
            $this->ssl->ca_path = null;
            $this->ssl->type = 'csr';
            $this->ssl->status = SslStatus::CREATED;
            $this->ssl->is_active = false;
            $this->ssl->save();

            $this->broadcastSslEvent($this->ssl, $this->server->project_id);
        });
    }

    public function failed(Exception $e): void
    {
        $this->handleSslFailure($this->ssl, $e, $this->server->project_id, 'deactivate-server-ssl-failed');
    }
}
