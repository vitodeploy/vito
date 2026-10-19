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

class DeleteServerSslJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public bool $deleteWhenMissingModels = true;

    public function __construct(protected Server $server, protected Ssl $ssl) {}

    public function handle(): void
    {
        $this->run("server-{$this->server->id}", function () {
            if (! $this->ssl->fresh()) {
                return;
            }
            $this->ssl->refresh();
            if ($this->ssl->server_id !== $this->server->id) {
                return;
            }
            if ($this->ssl->environmentDomains()->exists() || $this->ssl->hostedDomains()->exists()) {
                if ($this->ssl->status === SslStatus::DELETING) {
                    $this->ssl->status = SslStatus::CREATED;
                    $this->ssl->save();
                    $this->broadcastSslUpdate();
                }
                return;
            }
            if ($this->ssl->environment_id) {
                app(\App\Actions\EnvironmentDomain\DeleteEnvironmentDomain::class)->removeUnusedCertificate($this->ssl);

                return;
            }
            $ssh = $this->server->ssh()->setLog($this->ssl->log);

            $result = $ssh->exec(view('ssh.ssl.delete-ssl', [
                'sslId' => $this->ssl->id,
                'isWildcard' => $this->ssl->is_wildcard,
            ]));

            if (! Str::contains($result, 'SSL DELETED SUCCESSFULLY')) {
                throw new Exception('SSL deletion failed: '.$result);
            }

            $this->broadcastSslUpdate('ssl.deleted');

            $this->ssl->delete();
        });
    }

    public function failed(Exception $e): void
    {
        if (! $this->ssl->fresh() || $this->ssl->environmentDomains()->exists() || $this->ssl->hostedDomains()->exists()) {
            return;
        }
        $this->ssl->status = SslStatus::FAILED;
        $this->ssl->save();
        $this->broadcastSslUpdate();

        ServerLog::log(
            $this->server,
            'delete-server-ssl-failed',
            $e->getMessage(),
        );
    }

    private function broadcastSslUpdate(string $type = 'ssl.updated'): void
    {
        $this->ssl->refresh();

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $this->server->project_id,
            type: $type,
            data: new SslResource($this->ssl),
        ));
    }
}
