<?php

namespace App\Contracts;

interface SourceControlProvider
{
    public function connect(): bool;

    public function getRepo(string $repo = null): mixed;

    public function fullRepoUrl(string $repo): string;

    public function deployHook(string $repo, array $events, string $secret): array;

    public function destroyHook(string $repo, string $hookId): void;

    public function getLastCommit(string $repo, string $branch): ?array;
}
