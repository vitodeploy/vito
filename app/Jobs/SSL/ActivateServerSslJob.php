<?php

namespace App\Jobs\SSL;

use App\DTOs\SocketEventDTO;
use App\Enums\SslStatus;
use App\Events\SocketEvent;
use App\Http\Resources\SslResource;
use App\Models\Server;
use App\Models\ServerLog;
use App\Models\Ssl;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class ActivateServerSslJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(protected Server $server, protected Ssl $ssl) {}

    public function handle(): void
    {
        $this->run("server-{$this->server->id}", function () {
            $ssh = $this->server->ssh()->setLog($this->ssl->log);

            $basePath = '/etc/ssl/vito/'.$this->ssl->id;

            $pkPath = $this->ssl->csr_data['pk_path'];

            $ssh->write($basePath.'/cert.pem', $this->ssl->certificate, 'root');

            if ($this->ssl->ca) {
                $ssh->write($basePath.'/ca.pem', $this->ssl->ca, 'root');
            }

            $this->ssl->certificate_path = $basePath.'/cert.pem';
            $this->ssl->pk_path = $pkPath;
            $this->ssl->ca_path = $this->ssl->ca ? $basePath.'/ca.pem' : null;
            $this->ssl->type = 'custom';
            $this->ssl->status = SslStatus::CREATED;
            $this->ssl->save();

            $result = $ssh->exec(view('ssh.ssl.activate-ssl', [
                'passphrase' => $this->ssl->csr_passphrase,
                'pkPath' => $pkPath,
                'decryptedPath' => $basePath.'/private.decrypted.key',
            ]));

            if (! Str::contains($result, 'SSL ACTIVATED SUCCESSFULLY')) {
                throw new Exception('SSL activation failed: '.$result);
            }

            $this->broadcastSslUpdate();
        });
    }

    public function failed(Exception $e): void
    {
        $this->ssl->status = SslStatus::FAILED;
        $this->ssl->save();
        $this->broadcastSslUpdate();

        ServerLog::log(
            $this->server,
            'activate-server-ssl-failed',
            $e->getMessage(),
        );
    }

    private function broadcastSslUpdate(): void
    {
        $this->ssl->refresh();

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $this->server->project_id,
            type: 'ssl.updated',
            data: new SslResource($this->ssl),
        ));
    }
}
