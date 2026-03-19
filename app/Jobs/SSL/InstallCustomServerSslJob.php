<?php

namespace App\Jobs\SSL;

use App\Enums\SslStatus;
use App\Models\Server;
use App\Models\Ssl;
use App\Traits\BroadcastsSslEvents;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class InstallCustomServerSslJob implements ShouldQueue
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

            $ssh->exec('sudo mkdir -p '.$basePath);
            $ssh->write($basePath.'/cert.pem', $this->ssl->certificate, 'root');
            $ssh->write($basePath.'/private.key', $this->ssl->pk, 'root');

            if ($this->ssl->ca) {
                $ssh->write($basePath.'/ca.pem', $this->ssl->ca, 'root');
            }

            $ssh->exec('sudo chmod 600 '.$basePath.'/private.key');

            $this->ssl->certificate_path = $basePath.'/cert.pem';
            $this->ssl->pk_path = $basePath.'/private.key';
            $this->ssl->ca_path = $this->ssl->ca ? $basePath.'/ca.pem' : null;
            $this->ssl->status = SslStatus::CREATED;
            $this->ssl->is_active = true;
            $this->ssl->save();

            $this->broadcastSslEvent($this->ssl, $this->server->project_id);
        });
    }

    public function failed(Exception $e): void
    {
        $this->handleSslFailure($this->ssl, $e, $this->server->project_id, 'install-custom-server-ssl-failed');
    }
}
