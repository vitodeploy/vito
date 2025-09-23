<?php

namespace App\Traits;

trait NormalizesWebDirectory
{
    private function normalizeWebDirectory(?string $webDirectory): ?string
    {
        if (empty($webDirectory)) {
            return null;
        }

        // Remove leading and trailing slashes
        $webDirectory = trim($webDirectory, '/');

        // If it's empty after trimming, return null
        if (empty($webDirectory)) {
            return null;
        }

        return $webDirectory;
    }
}
