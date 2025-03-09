<?php

namespace App\SourceControlProviders;

use App\Exceptions\FailedToDeployGitKey;
use App\Exceptions\FailedToDestroyGitHook;

interface SourceControlProvider
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function createRules(array $input): array;

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function createData(array $input): array;

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function editRules(array $input): array;

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function editData(array $input): array;

    /**
     * @return array<string, mixed>
     */
    public function data(): array;

    public function connect(): bool;

    public function getRepo(?string $repo = null): mixed;

    public function fullRepoUrl(string $repo, string $key): string;

    /**
     * @param  array<mixed>  $events
     * @return array<string, mixed>
     *
     * @throws \App\Exceptions\FailedToDeployGitHook
     */
    public function deployHook(string $repo, array $events, string $secret): array;

    /**
     * @throws FailedToDestroyGitHook
     */
    public function destroyHook(string $repo, string $hookId): void;

    /**
     * @return array<string, mixed>
     */
    public function getLastCommit(string $repo, string $branch): ?array;

    /**
     * @throws FailedToDeployGitKey
     */
    public function deployKey(string $title, string $repo, string $key): void;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function getWebhookBranch(array $payload): string;
}
