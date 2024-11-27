<?php

namespace App\SourceControlProviders;

use App\Exceptions\FailedToDeployGitKey;
use App\Exceptions\FailedToDestroyGitHook;

interface SourceControlProvider
{
    public function createRules(array $input): array;

    public function createData(array $input): array;

    public function editRules(array $input): array;

    public function editData(array $input): array;

    public function data(): array;

    public function connect(): bool;

    public function getRepo(?string $repo = null): mixed;

    public function fullRepoUrl(string $repo, string $key): string;

    public function deployHook(string $repo, array $events, string $secret): array;

    /**
     * @throws FailedToDestroyGitHook
     */
    public function destroyHook(string $repo, string $hookId): void;

    public function getLastCommit(string $repo, string $branch): ?array;

    /**
     * @throws FailedToDeployGitKey
     */
    public function deployKey(string $title, string $repo, string $key): void;
}
