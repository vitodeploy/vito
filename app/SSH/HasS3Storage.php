<?php

namespace App\SSH;

trait HasS3Storage
{
    private function prepareS3Path(string $path, string $prefix = ''): string
    {
        $path = trim($path);
        $path = ltrim($path, '/');
        $path = preg_replace('/[^a-zA-Z0-9\-_\.\/]/', '_', $path);
        $path = preg_replace('/\/+/', '/', $path);

        if ($prefix) {
            $path = trim($prefix, '/').'/'.$path;
        }

        return $path;
    }
}
