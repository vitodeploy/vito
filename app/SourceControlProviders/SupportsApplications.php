<?php

namespace App\SourceControlProviders;

interface SupportsApplications extends SourceControlProvider
{
    /** @return array<int, mixed> */
    public function repositoryRules(): array;

    public function deployReadOnlyKey(string $title, string $repo, string $key): string;

    public function findDeployKey(string $repo, string $key): ?string;

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    public function getPushBranches(array $payload): array;
}
