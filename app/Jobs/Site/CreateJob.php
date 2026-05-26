<?php

namespace App\Jobs\Site;

use App\DTOs\SocketEventDTO;
use App\Enums\SiteStatus;
use App\Events\SiteCreatedEvent;
use App\Events\SocketEvent;
use App\Exceptions\FailedToDeployGitKey;
use App\Exceptions\RepositoryNotFound;
use App\Exceptions\RepositoryPermissionDenied;
use App\Exceptions\SourceControlIsNotConnected;
use App\Exceptions\SSHCommandError;
use App\Exceptions\SSHConnectionError;
use App\Facades\Notifier;
use App\Http\Resources\SiteResource;
use App\Jobs\HostedDomain\CheckDomainJob;
use App\Models\ServerLog;
use App\Models\Site;
use App\Notifications\SiteInstallationFailed;
use App\Notifications\SiteInstallationSucceed;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CreateJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(protected Site $site)
    {
        $this->onQueue('ssh');
    }

    public function handle(): void
    {
        $this->run("server-{$this->site->server_id}", function () {
            $this->site->type()->install();
            $this->site->status = SiteStatus::READY;
            $this->site->progress = 100;
            $this->site->progress_step = null;
            $this->site->last_error = null;
            $this->site->save();
            $this->broadcastSiteUpdate();
            Notifier::send($this->site, new SiteInstallationSucceed($this->site));
            SiteCreatedEvent::dispatch($this->site);

            foreach ($this->site->hostedDomains as $hostedDomain) {
                dispatch(new CheckDomainJob($hostedDomain))->onQueue('ssh');
            }
        });
    }

    public function failed(Exception $e): void
    {
        $this->site->status = SiteStatus::INSTALLATION_FAILED;
        $this->site->last_error = $this->safeErrorSummary($e);
        $this->site->save();
        $this->broadcastSiteUpdate();
        ServerLog::log(
            $this->site->server,
            'site-installation-failed',
            $this->safeLogMessage($e),
            $this->site
        );
        Notifier::send($this->site, new SiteInstallationFailed($this->site));
    }

    private function safeLogMessage(Exception $e): string
    {
        if ($e instanceof FailedToDeployGitKey) {
            return 'Source control provider rejected the deploy key request. Provider response: '.$this->redactPublicKeys($e->getMessage());
        }

        return $e->getMessage();
    }

    private function safeErrorSummary(Exception $e): string
    {
        $messages = [
            SSHCommandError::class => 'An SSH command failed during installation. Check the site logs for the failing command.',
            SSHConnectionError::class => 'Could not connect to the server over SSH. Verify the server is reachable and try again.',
            RepositoryNotFound::class => 'Repository not found on the source control provider.',
            RepositoryPermissionDenied::class => 'Permission denied accessing the repository on the source control provider.',
            SourceControlIsNotConnected::class => 'Source control provider is not connected.',
        ];

        foreach ($messages as $class => $message) {
            if ($e instanceof $class) {
                return $message;
            }
        }

        if ($e instanceof FailedToDeployGitKey) {
            $response = trim($this->redactPublicKeys($e->getMessage()));
            if ($response === '') {
                return 'Source control provider rejected the deploy key request.';
            }

            return 'Source control provider rejected the deploy key request: '.$this->truncate($response, 500);
        }

        return 'Installation failed due to an unexpected error. See the site logs for full details.';
    }

    /**
     * Strip SSH public-key material from a string so provider responses can be
     * surfaced safely. Provider error bodies sometimes echo back the submitted
     * key — redact `ssh-rsa AAAA…`, `ssh-ed25519 AAAA…`, `ecdsa-sha2-… AAAA…`
     * and similar patterns.
     */
    private function redactPublicKeys(string $message): string
    {
        if ($message === '') {
            return $message;
        }

        $clipped = mb_substr($message, 0, 8192);

        $pattern = '/(ssh-(?:rsa|ed25519|dss)|ecdsa-sha2-[A-Za-z0-9-]+)\s+[A-Za-z0-9+\/=]+(?:\s+\S{1,128})?/';

        return (string) preg_replace($pattern, '[ssh public key redacted]', $clipped);
    }

    private function truncate(string $value, int $max): string
    {
        if (mb_strlen($value) <= $max) {
            return $value;
        }

        return mb_substr($value, 0, $max).'…';
    }

    private function broadcastSiteUpdate(): void
    {
        $this->site->refresh();

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $this->site->server->project_id,
            type: 'site.updated',
            data: new SiteResource($this->site),
        ));
    }
}
