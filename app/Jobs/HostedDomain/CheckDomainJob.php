<?php

namespace App\Jobs\HostedDomain;

use App\Actions\HostedDomain\ActivateHostedDomain;
use App\Actions\HostedDomain\VerifyHostedDomain;
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

    public function handle(VerifyHostedDomain $verifier, ActivateHostedDomain $activator): void
    {
        $result = $verifier->verify($this->hostedDomain);

        if ($result->verified) {
            $this->hostedDomain->error = null;
            $this->hostedDomain->save();
            $activator->activate($this->hostedDomain);
        } else {
            $this->hostedDomain->status = HostedDomainStatus::PENDING;
            $this->hostedDomain->error = $result->failureReason;
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
