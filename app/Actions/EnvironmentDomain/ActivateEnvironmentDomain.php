<?php

namespace App\Actions\EnvironmentDomain;

use App\Actions\Component\SyncComponentVhost;
use App\Actions\SSL\CheckSslExpiry;
use App\Actions\SSL\CreateLetsEncryptWildcardSsl;
use App\Enums\ApplicationStatus;
use App\Enums\ComponentStatus;
use App\Enums\EnvironmentStatus;
use App\Enums\HostedDomainStatus;
use App\Enums\SslMethod;
use App\Enums\SslStatus;
use App\Enums\SslType;
use App\Jobs\SSL\CreateLetsEncryptWildcardSslJob;
use App\Models\EnvironmentDomain;
use App\Models\ServerLog;
use App\Models\Ssl;
use App\Services\Webserver\Webserver;
use Illuminate\Validation\ValidationException;

class ActivateEnvironmentDomain
{
    public function activate(EnvironmentDomain $domain): void
    {
        $server = $domain->component->server;
        /** @var Webserver $webserver */
        $webserver = $server->webserver()->handler();
        if ($domain->ssl_method === SslMethod::CUSTOM) {
            if (! Ssl::activeServerLevel($server->id)->whereKey($domain->ssl_id)->exists()
                || ! app(SyncComponentVhost::class)->hasCertificate($domain)) {
                throw ValidationException::withMessages(['ssl_id' => 'The certificate is inactive, expired, or does not cover this host.']);
            }
        } elseif ($domain->ssl_method === SslMethod::LETSENCRYPT) {
            $wildcard = str_starts_with($domain->host, '*.');
            if ($wildcard || $webserver->createsSiteSSLs()) {
                $ssl = $domain->ssl;
                if (! $ssl || $ssl->environment_id !== $domain->environment_id || $ssl->server_id !== $server->id) {
                    $ssl = Ssl::query()->where('environment_id', $domain->environment_id)->where('server_id', $server->id)
                        ->where('status', SslStatus::CREATED)->get()->first(fn (Ssl $candidate): bool => is_array($candidate->domains) && $candidate->domains !== []
                            && $candidate->coversDomain($domain->host) && $candidate->certificate_path && $candidate->pk_path && $candidate->expires_at?->isFuture());
                }
                if (! $ssl && $wildcard) {
                    $projectDomain = app(ValidateEnvironmentDomain::class)->wildcardDomain($domain->environment, $domain->host);
                    app(CreateLetsEncryptWildcardSsl::class)->create($server, [
                        'domain_id' => $projectDomain->id, 'email' => $server->creator->email,
                    ], $domain);

                    return;
                }
                if (! $ssl) {
                    $ssl = new Ssl([
                        'server_id' => $server->id, 'type' => SslType::LETSENCRYPT->value,
                        'status' => SslStatus::CREATING, 'domains' => [$domain->host], 'email' => $server->creator->email,
                    ]);
                    $ssl->environment_id = $domain->environment_id;
                    $ssl->log_id = ServerLog::log($server, 'environment-domain-ssl', '')->id;
                    $ssl->save();
                }
                $domain->ssl_id = $ssl->id;
                $domain->setRelation('ssl', $ssl);
                $domain->save();
                if (! app(SyncComponentVhost::class)->hasCertificate($domain)) {
                    if ($wildcard) {
                        $domain->status = HostedDomainStatus::UPDATING;
                        $domain->save();
                        CreateLetsEncryptWildcardSslJob::dispatch($server, $ssl)->onQueue('ssh-certbot')->afterCommit();

                        return;
                    }
                    $this->issue($ssl);
                }
            }
        }
        $domain->status = HostedDomainStatus::ACTIVE;
        $domain->error = null;
        $domain->save();
        app(SyncComponentVhost::class)->sync($domain->component);
        app(BroadcastEnvironmentDomainUpdate::class)->broadcast($domain);
    }

    public function issue(Ssl $ssl): void
    {
        /** @var Webserver $webserver */
        $webserver = $ssl->server->webserver()->handler();
        $webserver->setupSSL($ssl);
        app(CheckSslExpiry::class)->check($ssl, notify: false);
        $ssl->status = SslStatus::CREATED;
        $ssl->save();
        foreach ($ssl->environmentDomains()->with('ssl', 'component')->get() as $domain) {
            if (! app(SyncComponentVhost::class)->hasCertificate($domain)) {
                throw ValidationException::withMessages(['ssl_id' => 'The generated certificate does not cover this host.']);
            }
        }
    }

    public function complete(Ssl $ssl): void
    {
        foreach ($ssl->environmentDomains()->with('ssl', 'environment.application', 'component.server')->get() as $domain) {
            if ($domain->environment->status !== EnvironmentStatus::READY
                || $domain->environment->application->status !== ApplicationStatus::READY) {
                continue;
            }
            if ($domain->component->status !== ComponentStatus::READY || ! $domain->component->server->isReady()) {
                if ($domain->status === HostedDomainStatus::UPDATING) {
                    $domain->status = HostedDomainStatus::PENDING;
                    $domain->error = 'Certificate issued. Activation will retry when the target is ready.';
                    $domain->save();
                    app(BroadcastEnvironmentDomainUpdate::class)->broadcast($domain);
                }

                continue;
            }
            if (app(SyncComponentVhost::class)->hasCertificate($domain)) {
                $domain->status = HostedDomainStatus::ACTIVE;
                $domain->error = null;
            } else {
                $domain->status = HostedDomainStatus::PENDING;
                $domain->error = 'The certificate is inactive, expired, or does not cover this host.';
            }
            $domain->save();
            app(SyncComponentVhost::class)->sync($domain->component);
            app(BroadcastEnvironmentDomainUpdate::class)->broadcast($domain);
        }
    }
}
