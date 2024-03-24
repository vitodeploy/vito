<?php

namespace App\SourceControlProviders;

interface SourceControlProvider
{
    public function createRules(array $input): array;

    public function createData(array $input): array;

    public function data(): array;

    public function connect(): bool;

    public function getRepo(?string $repo = null): mixed;

    public function fullRepoUrl(string $repo, string $key): string;

    public function deployHook(string $repo, array $events, string $secret): array;

    public function destroyHook(string $repo, string $hookId): void;

    public function getLastCommit(string $repo, string $branch): ?array;

    public function deployKey(string $title, string $repo, string $key): void;
}
