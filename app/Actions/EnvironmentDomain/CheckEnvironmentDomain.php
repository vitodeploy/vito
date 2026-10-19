<?php

namespace App\Actions\EnvironmentDomain;

use App\Actions\Component\SyncComponentVhost;
use App\Actions\HostedDomain\VerifyDomain;
use App\Enums\ApplicationStatus;
use App\Enums\ComponentStatus;
use App\Enums\EnvironmentStatus;
use App\Enums\HostedDomainStatus;
use App\Enums\SslStatus;
use App\Models\Application;
use App\Models\Environment;
use App\Models\EnvironmentDomain;
use Illuminate\Support\Facades\DB;

class CheckEnvironmentDomain
{
    public function check(int $domainId, int $serverId): void
    {
        $failure = DB::transaction(function () use ($domainId, $serverId): ?\Throwable {
            $domain = EnvironmentDomain::query()->with('environment.application', 'component.server')->find($domainId);
            if (! $domain) {
                return null;
            }
            Application::query()->lockForUpdate()->findOrFail($domain->environment->application_id);
            Environment::query()->lockForUpdate()->findOrFail($domain->environment_id);
            $domain->refresh();
            if ($domain->status !== HostedDomainStatus::PENDING || $domain->component->server_id !== $serverId
                || $domain->component->status !== ComponentStatus::READY || ! $domain->component->server->isReady()
                || ($domain->component->type !== \App\Enums\ComponentType::LOAD_BALANCER && ! $domain->component->capabilities()->where('capability', 'web')->where('status', ComponentStatus::READY)->exists())
                || $domain->environment->status !== EnvironmentStatus::READY || $domain->environment->application->status !== ApplicationStatus::READY) {
                return null;
            }
            $sync = app(SyncComponentVhost::class);
            $sync->sync($domain->component);
            $host = str_starts_with($domain->host, '*.') ? bin2hex(random_bytes(8)).substr($domain->host, 1) : $domain->host;
            $result = app(VerifyDomain::class)->verify($domain->component->server, $host, $sync->verificationKey($domain->environment), 'environment:'.$domain->environment_id);
            if ($result->verified) {
                try {
                    app(ActivateEnvironmentDomain::class)->activate($domain);
                } catch (\Throwable $e) {
                    $domain->status = HostedDomainStatus::PENDING;
                    $domain->save();
                    $this->failed($domainId, $serverId);

                    return $e;
                }
            } else {
                $domain->error = $result->failureReason;
                $domain->save();
            }
            app(BroadcastEnvironmentDomainUpdate::class)->broadcast($domain);

            return null;
        });
        if ($failure) {
            throw $failure;
        }
    }

    public function failed(int $domainId, int $serverId, ?int $sslId = null): void
    {
        DB::transaction(function () use ($domainId, $serverId, $sslId): void {
            $domain = EnvironmentDomain::query()->with('environment.application', 'component')->find($domainId);
            if (! $domain) {
                return;
            }
            Application::query()->lockForUpdate()->findOrFail($domain->environment->application_id);
            Environment::query()->lockForUpdate()->findOrFail($domain->environment_id);
            $domain->refresh();
            if ($domain->component->server_id !== $serverId
                || $domain->environment->status !== EnvironmentStatus::READY || $domain->environment->application->status !== ApplicationStatus::READY
                || ($sslId === null && $domain->status !== HostedDomainStatus::PENDING)
                || ($sslId !== null && ($domain->ssl_id !== $sslId || $domain->status !== HostedDomainStatus::UPDATING))) {
                return;
            }
            $domain->status = HostedDomainStatus::PENDING;
            $domain->error = 'Domain verification or certificate provisioning failed. Check DNS, certificate coverage, and server connectivity, then retry.';
            $domain->save();
            if ($domain->ssl?->environment_id && $domain->ssl->status === SslStatus::CREATING) {
                $domain->ssl->status = SslStatus::FAILED;
                $domain->ssl->save();
            }
            app(BroadcastEnvironmentDomainUpdate::class)->broadcast($domain);
        });
    }
}
