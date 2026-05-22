<?php

namespace App\SSH\OS;

use App\Exceptions\SSHError;
use App\Models\Site;

class Git
{
    /**
     * @throws SSHError
     */
    public function clone(Site $site, ?string $path = null): void
    {
        $usesToken = $site->sourceControl?->isGithubApp() ?? false;
        $ssh = $site->server->ssh($site->user);

        if ($usesToken) {
            $ssh = $ssh->variables($site->environmentVariables());
        }

        $repoUrl = (string) $site->getFullRepositoryUrl();
        $ssh->exec(
            view('ssh.git.clone', [
                'host' => $usesToken ? '' : str($repoUrl)->after('@')->before('-'),
                'repo' => escapeshellarg($repoUrl),
                'path' => escapeshellarg((string) ($path ?? $site->path)),
                'branch' => escapeshellarg((string) $site->branch),
                'key' => $site->getSshKeyName(),
                'port' => $site->sourceControl?->provider()?->getSshPort() ?? 22,
                'usesToken' => $usesToken,
            ]),
            'clone-repository',
            $site->id
        );
    }

    /**
     * @throws SSHError
     */
    public function checkout(Site $site): void
    {
        $site->server->ssh($site->user)->exec(
            view('ssh.git.checkout', [
                'path' => escapeshellarg((string) $site->path),
                'branch' => escapeshellarg((string) $site->branch),
            ]),
            'checkout-branch',
            $site->id
        );
    }

    /**
     * @throws SSHError
     */
    public function fetchOrigin(Site $site): void
    {
        $ssh = $site->server->ssh($site->user);

        if ($site->sourceControl?->isGithubApp()) {
            $ssh = $ssh->variables($site->environmentVariables());
        }

        $ssh->exec(
            view('ssh.git.fetch-origin', [
                'path' => escapeshellarg((string) $site->path),
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
