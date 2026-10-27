<?php

namespace App\SSH\OS;

use App\DTOs\GitCloneDTO;
use App\Exceptions\SSHError;
use App\Models\Site;
use RuntimeException;

class Git
{
    /**
     * @throws SSHError
     */
    public function clone(Site $site, ?string $path = null): void
    {
        $usesToken = $site->sourceControl?->isGithubApp() ?? false;

        $this->cloneRepository(new GitCloneDTO(
            server: $site->server,
            user: $site->user ?? $site->server->getSshUser(),
            repoUrl: (string) $site->getFullRepositoryUrl(),
            branch: (string) $site->branch,
            path: $path ?? $site->path,
            keyName: $site->getSshKeyName(),
            port: $site->sourceControl?->provider()->getSshPort() ?? 22,
            usesToken: $usesToken,
            variables: $usesToken ? $site->environmentVariables() : [],
            siteId: $site->id,
        ));
    }

    /**
     * @throws SSHError
     */
    public function cloneRepository(GitCloneDTO $target): void
    {
        $host = $target->usesToken ? '' : str($target->repoUrl)->after('@')->before(':')->replaceEnd('-'.$target->keyName, '')->toString();
        if (! $target->usesToken && preg_match('/\A[a-zA-Z0-9.-]+\z/', $host) !== 1) {
            throw new RuntimeException('Invalid SSH repository host.');
        }
        $target->server->ssh($target->user)->setLog($target->log)->variables($target->variables)->exec(
            view('ssh.git.clone', [
                'host' => $host,
                'repo' => escapeshellarg($target->repoUrl),
                'path' => escapeshellarg($target->path),
                'branch' => escapeshellarg($target->branch),
                'key' => $target->keyName,
                'port' => $target->port,
                'usesToken' => $target->usesToken,
            ]),
            'clone-repository',
            $target->siteId
        );

        if ($target->commit !== null) {
            $target->server->ssh($target->user)->setLog($target->log)->exec(
                view('ssh.git.checkout', [
                    'path' => escapeshellarg($target->path),
                    'branch' => escapeshellarg($target->commit),
                ]),
                'checkout-commit',
                $target->siteId
            );
        }
    }

    /**
     * @throws SSHError
     */
    public function checkout(Site $site, ?string $path = null): void
    {
        $site->server->ssh($site->user)->exec(
            view('ssh.git.checkout', [
                'path' => escapeshellarg((string) ($path ?? $site->path)),
                'branch' => escapeshellarg((string) $site->branch),
            ]),
            'checkout-branch',
            $site->id
        );
    }

    /**
     * @throws SSHError
     */
    public function fetchOrigin(Site $site, ?string $path = null): void
    {
        $ssh = $site->server->ssh($site->user);

        if ($site->sourceControl?->isGithubApp()) {
            $ssh = $ssh->variables($site->environmentVariables());
        }

        $ssh->exec(
            view('ssh.git.fetch-origin', [
                'path' => escapeshellarg((string) ($path ?? $site->path)),
            ]),
            'fetch-origin',
            $site->id
        );
    }

    /**
     * @throws SSHError
     */
    public function setRemote(Site $site, string $newRepoUrl): void
    {
        $usesToken = $site->sourceControl?->isGithubApp() ?? false;
        $ssh = $site->server->ssh($site->user);

        if ($usesToken) {
            $ssh = $ssh->variables($site->environmentVariables());
        }

        $ssh->exec(
            view('ssh.git.set-remote', [
                'path' => escapeshellarg((string) $site->path),
                'newRepoUrl' => escapeshellarg($newRepoUrl),
                'usesToken' => $usesToken,
            ]),
            'set-remote',
            $site->id
        );
    }
}
