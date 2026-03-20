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

class CreateServerCsrJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(protected Server $server, protected Ssl $ssl) {}

    public function handle(): void
    {
        $this->run("server-{$this->server->id}", function () {
            $csrData = $this->ssl->csr_data;
            $keySize = $csrData['key_size'] ?? 2048;
            $ssh = $this->server->ssh()->setLog($this->ssl->log);

            $configContent = view('ssh.ssl.csr-config', [
                'keySize' => $keySize,
                'commonName' => $csrData['common_name'],
                'organization' => $csrData['organization'],
                'organizationalUnit' => $csrData['organizational_unit'] ?? null,
                'city' => $csrData['city'],
                'state' => $csrData['state'],
                'country' => $csrData['country'],
                'email' => $csrData['email'] ?? null,
            ]);

            $configPath = '/etc/ssl/vito/'.$this->ssl->id.'/openssl.cnf';

            $ssh->exec('sudo mkdir -p /etc/ssl/vito/'.$this->ssl->id);
            $ssh->write($configPath, $configContent, 'root');

            $command = view('ssh.ssl.create-csr', [
                'sslId' => $this->ssl->id,
                'keySize' => $keySize,
                'passphrase' => $this->ssl->csr_passphrase,
            ]);

            $result = $ssh->exec($command);

            if (! Str::contains($result, 'CSR GENERATED SUCCESSFULLY')) {
                throw new Exception('CSR generation failed: '.$result);
            }

            $csrData['csr_path'] = '/etc/ssl/vito/'.$this->ssl->id.'/request.csr';
            $csrData['pk_path'] = '/etc/ssl/vito/'.$this->ssl->id.'/private.key';

            $this->ssl->csr_data = $csrData;
            $this->ssl->status = SslStatus::CREATED;
            $this->ssl->save();

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
            'create-server-csr-failed',
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
