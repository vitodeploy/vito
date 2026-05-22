<?php

namespace App\Jobs\Site;

use App\DTOs\SocketEventDTO;
use App\Enums\SiteStatus;
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

    /**
     * Build a log-safe error message. Provider exceptions like FailedToDeployGitKey
     * can carry HTTP response bodies that echo back submitted public keys, so we
     * strip them out before persisting to ServerLog (which is readable by any
     * project member with log access).
     */
    private function safeLogMessage(Exception $e): string
    {
        if ($e instanceof FailedToDeployGitKey) {
            return 'Source control provider rejected the deploy key request. Provider response withheld from log to avoid leaking key material — check the provider audit log for details.';
        }

        return $e->getMessage();
    }

    /**
     * Build a UI-safe error summary. Exception messages can contain provider
     * payloads (e.g. FailedToDeployGitKey carries the raw HTTP response body
     * which echoes back submitted public keys), so we never include $e->getMessage()
     * verbatim — full details live in ServerLog.
     */
    private function safeErrorSummary(Exception $e): string
    {
        $messages = [
            SSHCommandError::class => 'An SSH command failed during installation. Check the site logs for the failing command.',
            SSHConnectionError::class => 'Could not connect to the server over SSH. Verify the server is reachable and try again.',
            FailedToDeployGitKey::class => 'Failed to deploy the SSH deploy key to the source control provider. The provider rejected the request — check that the repository is accessible and that the key is not already in use.',
            RepositoryNotFound::class => 'Repository not found on the source control provider.',
            RepositoryPermissionDenied::class => 'Permission denied accessing the repository on the source control provider.',
            SourceControlIsNotConnected::class => 'Source control provider is not connected.',
        ];

        foreach ($messages as $class => $message) {
            if ($e instanceof $class) {
                return $message;
            }
        }

        return sprintf('Installation failed (%s). See the site logs for full details.', class_basename($e));
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
