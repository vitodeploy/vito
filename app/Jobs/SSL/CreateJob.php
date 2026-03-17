<?php

namespace App\Jobs\SSL;

use App\Actions\Application\DeployApplication;
use App\DTOs\SocketEventDTO;
use App\Enums\SslStatus;
use App\Events\SocketEvent;
use App\Http\Resources\SslResource;
use App\Models\Application;
use App\Models\ServerLog;
use App\Models\Service;
use App\Models\Site;
use App\Models\Ssl;
use App\Services\Webserver\Webserver;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CreateJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(protected Site|Application $parent, protected Ssl $ssl) {}

    public function handle(): void
    {
        $this->run("server-{$this->parent->server_id}", function () {
            /** @var Service $service */
            $service = $this->parent->server->webserver();
            /** @var Webserver $webserver */
            $webserver = $service->handler();
            $webserver->setupSSL($this->ssl);
            $this->ssl->status = SslStatus::CREATED;
            $this->ssl->save();
            $this->broadcastSslUpdate();

            if ($this->parent instanceof Application) {
                app(DeployApplication::class)->deploy($this->parent->refresh());
            } else {
                $webserver->updateVHost($this->parent->refresh(), regenerate: [
                    'port',
                ]);
            }
        });
    }

    public function failed(Exception $e): void
    {
        $this->ssl->status = SslStatus::FAILED;
        $this->ssl->save();
        $this->broadcastSslUpdate();

        ServerLog::log(
            $this->parent->server,
            'create-ssl-failed',
            $e->getMessage(),
        );
    }

    private function broadcastSslUpdate(): void
    {
        $this->ssl->refresh();

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $this->parent->server->project_id,
            type: 'ssl.updated',
            data: new SslResource($this->ssl),
        ));
    }
}
