<?php

namespace App\SourceControlProviders;

class Custom extends AbstractSourceControlProvider
{
    public function connect(): bool
    {
        return true;
    }

    public function getRepo(string $repo = null): string
    {
        return '';
    }

    public function fullRepoUrl(string $repo): string
    {
        return $repo;
    }

    public function deployHook(string $repo, array $events, string $secret): array
    {
        return [];
    }

    public function destroyHook(string $repo, string $hookId): void
    {
        // TODO: Implement destroyHook() method.
    }

    public function getLastCommit(string $repo, string $branch): ?array
    {
        return null;
    }
}
