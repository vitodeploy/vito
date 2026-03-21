<?php

namespace App\Jobs\HostedDomain;

use App\Actions\HostedDomain\ActivateHostedDomain;
use App\Actions\HostedDomain\CheckDomainResolution;
use App\DTOs\SocketEventDTO;
use App\Enums\HostedDomainStatus;
use App\Events\SocketEvent;
use App\Http\Resources\HostedDomainResource;
use App\Models\HostedDomain;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckDomainJob implements ShouldQueue
{
    use Queueable;

    public function __construct(protected HostedDomain $hostedDomain) {}

    public function handle(): void
    {
        $site = $this->hostedDomain->site;
        $server = $site->server;

        $result = app(CheckDomainResolution::class)->check($this->hostedDomain, $server);

        if ($result['resolves']) {
            $this->hostedDomain->error = null;
            $this->hostedDomain->save();
            app(ActivateHostedDomain::class)->activate($this->hostedDomain);
        } else {
            $resolvedIps = $result['resolved_ips'];
            $this->hostedDomain->error = empty($resolvedIps)
                ? 'Unable to resolve this domain to the server'
                : 'Domain incorrectly resolves to '.implode(', ', $resolvedIps);
            $this->hostedDomain->status = HostedDomainStatus::PENDING;
            $this->hostedDomain->save();
        }

        $this->broadcastUpdate();
    }

    private function broadcastUpdate(): void
    {
        $this->hostedDomain->refresh();

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $this->hostedDomain->site->server->project_id,
            type: 'hosted-domain.updated',
            data: new HostedDomainResource($this->hostedDomain),
        ));
    }
}
