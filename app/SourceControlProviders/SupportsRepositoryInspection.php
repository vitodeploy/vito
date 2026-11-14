<?php

namespace App\SourceControlProviders;

interface SupportsRepositoryInspection
{
    public function getDefaultBranch(string $repo): ?string;

    /**
     * Reads a file from the repository, or null when the provider has no such file.
     */
    public function getFileContents(string $repo, string $branch, string $path): ?string;
}
