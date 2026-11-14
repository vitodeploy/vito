<?php

namespace App\Actions\EnvironmentDomain;

use App\Actions\Component\SyncComponentVhost;
use App\Enums\EnvironmentStatus;
use App\Jobs\SSL\DeleteServerSslJob;
use App\Models\Environment;
use App\Models\EnvironmentDomain;
use App\Models\Ssl;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteEnvironmentDomain
{
    public function delete(EnvironmentDomain $domain): void
    {
        app(ValidateEnvironmentDomain::class)->run($domain->environment, function () use ($domain): void {
            $domain->refresh();
            if ($domain->redirects()->exists()) {
                throw ValidationException::withMessages(['host' => 'Remove the redirects using this alias before deleting it.']);
            }
            $ssl = $domain->ssl;
            $this->remove($domain);
            $this->queueUnusedCertificate($ssl);
        });
    }

    public function removeForServer(int $environmentId, int $serverId): void
    {
        $environment = Environment::query()->find($environmentId);
        if (! $environment || $environment->status !== EnvironmentStatus::DELETING) {
            return;
        }
        foreach ($environment->domains()->whereHas('component', fn ($query) => $query->where('server_id', $serverId))
            ->orderByRaw('redirect_to_domain_id IS NULL')->orderBy('id')->get() as $domain) {
            DB::transaction(fn () => $this->remove($domain));
        }
        foreach (Ssl::query()->with('server')->where('environment_id', $environmentId)->where('server_id', $serverId)->doesntHave('environmentDomains')->get() as $ssl) {
            $this->removeUnusedCertificate($ssl);
        }
    }

    public function remove(EnvironmentDomain $domain): void
    {
        $component = $domain->component;
        $domain->delete();
        app(SyncComponentVhost::class)->sync($component);
        app(BroadcastEnvironmentDomainUpdate::class)->broadcast($domain);
    }

    public function queueUnusedCertificate(?Ssl $ssl): void
    {
        if ($ssl?->environment_id && $ssl->server && ! $ssl->environmentDomains()->exists()) {
            DeleteServerSslJob::dispatch($ssl->server, $ssl)->onQueue('ssh')->afterCommit();
        }
    }

    public function removeUnusedCertificate(?Ssl $ssl): void
    {
        if (! $ssl || ! $ssl->environment_id || $ssl->environmentDomains()->exists()) {
            return;
        }
        $ssl->server?->ssh()->exec(view('ssh.ssl.delete-environment-ssl', ['ssl' => $ssl]), 'delete-environment-ssl');
        $ssl->delete();
    }
}
