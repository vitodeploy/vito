<?php

namespace App\Jobs\HostedDomain;

use App\Actions\HostedDomain\ActivateHostedDomain;
use App\Actions\SSL\CertificateParser;
use App\DTOs\SocketEventDTO;
use App\Enums\HostedDomainStatus;
use App\Enums\SslMethod;
use App\Enums\SslStatus;
use App\Enums\SslType;
use App\Events\SocketEvent;
use App\Http\Resources\HostedDomainResource;
use App\Models\HostedDomain;
use App\Models\ServerLog;
use App\Models\Service;
use App\Models\Site;
use App\Models\Ssl;
use App\Services\Webserver\Webserver;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SetupHostedDomainSslJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public int $timeout = 300;

    public function __construct(protected HostedDomain $hostedDomain) {}

    public function handle(): void
    {
        $site = $this->hostedDomain->site;

        $this->run("site-ssl-{$site->id}", function () use ($site): void {
            $this->hostedDomain->refresh();
            if ($this->hostedDomain->ssl_id) {
                $ssl = $this->hostedDomain->ssl;
                if ($ssl && $ssl->status === SslStatus::CREATED && $ssl->coversDomain($this->hostedDomain->domain)) {
                    app(ActivateHostedDomain::class)->activate($this->hostedDomain);
                    $this->broadcastUpdate();

                    return;
                }
            }

            /** @var Service $service */
            $service = $site->server->webserver();
            /** @var Webserver $webserver */
            $webserver = $service->handler();

            $leDomains = $site->hostedDomains()
                ->where('ssl_method', SslMethod::LETSENCRYPT)
                ->whereIn('status', [HostedDomainStatus::UPDATING, HostedDomainStatus::ACTIVE])
                ->pluck('domain')
                ->push($this->hostedDomain->domain)
                ->unique()
                ->values()
                ->toArray();

            $ssl = $site->ssls()
                ->where('type', SslType::LETSENCRYPT)
                ->whereNotIn('status', [SslStatus::FAILED, SslStatus::DELETING])
                ->first();

            if ($ssl) {
                $ssl->domains = $leDomains;
                $ssl->status = SslStatus::CREATING;
                $ssl->save();
            } else {
                $email = $this->resolveEmail($site);
                $ssl = new Ssl([
                    'site_id' => $site->id,
                    'type' => SslType::LETSENCRYPT->value,
                    'status' => SslStatus::CREATING,
                    'email' => $email,
                ]);
                $ssl->domains = $leDomains;
                $ssl->log_id = ServerLog::log($site->server, 'setup-hosted-domain-ssl', '', $site)->id;
                $ssl->save();
            }

            $webserver->setupSSL($ssl);

            $this->readAndVerifyCertificate($site, $ssl, $leDomains);
        });
    }

    public function failed(Exception $e): void
    {
        $site = $this->hostedDomain->site;
        $errorMessage = 'Unable to generate SSL certificate. Check server logs for details.';

        $ssl = $site->ssls()
            ->where('type', SslType::LETSENCRYPT)
            ->where('status', SslStatus::CREATING)
            ->first();

        if ($ssl) {
            $ssl->status = SslStatus::CREATED;
            $ssl->save();
        }

        $updatingDomains = $site->hostedDomains()
            ->where('ssl_method', SslMethod::LETSENCRYPT)
            ->where('status', HostedDomainStatus::UPDATING)
            ->get();

        foreach ($updatingDomains as $domain) {
            if ($ssl && $ssl->coversDomain($domain->domain)) {
                $domain->error = $errorMessage;
                $domain->status = HostedDomainStatus::ACTIVE;
            } else {
                $domain->error = $errorMessage;
                $domain->status = HostedDomainStatus::PENDING;
            }
            $domain->save();
            $this->broadcastUpdateFor($domain);
        }

        $this->hostedDomain->refresh();
        if ($this->hostedDomain->status === HostedDomainStatus::UPDATING) {
            $this->hostedDomain->error = $errorMessage;
            $this->hostedDomain->status = HostedDomainStatus::PENDING;
            $this->hostedDomain->save();
            $this->broadcastUpdate();
        }

        ServerLog::log(
            $site->server,
            'setup-hosted-domain-ssl-failed',
            $e->getMessage(),
            $site
        );
    }

    private function readAndVerifyCertificate(Site $site, Ssl $ssl, array $leDomains): void
    {
        $certPath = "/etc/letsencrypt/live/{$ssl->id}/fullchain.pem";
        $certificate = trim($site->server->ssh()->exec("sudo cat {$certPath}"));

        if (empty($certificate) || ! str_contains($certificate, 'BEGIN CERTIFICATE')) {
            throw new Exception('Failed to read generated certificate from server');
        }

        $parsed = CertificateParser::parse($certificate);
        $certDomains = array_map('strtolower', $parsed['domains']);

        $ssl->domains = $parsed['domains'];
        $ssl->expires_at = $parsed['expires_at'];
        $ssl->status = SslStatus::CREATED;
        $ssl->save();

        $leHostedDomains = $site->hostedDomains()
            ->where('ssl_method', SslMethod::LETSENCRYPT)
            ->whereIn('domain', $leDomains)
            ->get();

        foreach ($leHostedDomains as $hd) {
            if ($this->domainInList($hd->domain, $certDomains)) {
                $hd->ssl_id = $ssl->id;
                $hd->error = null;
                $hd->status = HostedDomainStatus::ACTIVE;
                $hd->save();
            } else {
                $hd->error = 'Unable to validate generated SSL covers this domain';
                $hd->status = HostedDomainStatus::PENDING;
                $hd->save();
            }

            $this->broadcastUpdateFor($hd);
        }

        $this->hostedDomain->refresh();
        if ($this->domainInList($this->hostedDomain->domain, $certDomains)) {
            app(ActivateHostedDomain::class)->activate($this->hostedDomain);
            $this->broadcastUpdate();
        }
    }

    private function domainInList(string $domain, array $certDomains): bool
    {
        $lower = strtolower($domain);

        foreach ($certDomains as $certDomain) {
            if ($certDomain === $lower) {
                return true;
            }
            if (Ssl::wildcardMatches($certDomain, $lower)) {
                return true;
            }
        }

        return false;
    }

    private function resolveEmail(Site $site): string
    {
        $existingSsl = $site->ssls()
            ->where('type', SslType::LETSENCRYPT)
            ->whereNotNull('email')
            ->first();

        if ($existingSsl?->email) {
            return $existingSsl->email;
        }

        /** @var \App\Models\UserProject|null $userProject */
        $userProject = $site->server->project->users()->with('user')->first();
        if ($userProject?->user?->email) {
            return $userProject->user->email;
        }

        throw new Exception('No email address available for Let\'s Encrypt certificate generation');
    }

    private function broadcastUpdate(): void
    {
        $this->broadcastUpdateFor($this->hostedDomain->refresh());
    }

    private function broadcastUpdateFor(HostedDomain $hostedDomain): void
    {
        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $hostedDomain->site->server->project_id,
            type: 'hosted-domain.updated',
            data: new HostedDomainResource($hostedDomain),
        ));
    }
}
