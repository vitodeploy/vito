<?php

namespace App\SourceControlProviders;

interface SupportsRepositoryInspection
{
    public function getDefaultBranch(string $repo): ?string;

    public function getFileContents(string $repo, string $branch, string $path): ?string;
}
