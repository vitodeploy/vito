<?php

namespace App\Traits;

trait NormalizesWebDirectory
{
    private function normalizeWebDirectory(?string $webDirectory): ?string
    {
        if (empty($webDirectory)) {
            return null;
        }

        $webDirectory = trim($webDirectory, '/');

        if (empty($webDirectory)) {
            return null;
        }

        return $webDirectory;
    }
}
