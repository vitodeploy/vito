<?php

namespace App\RuntimePresets;

use App\Enums\WebMode;
use App\Support\RepositoryFiles;

class StaticSite extends AbstractRuntimePreset
{
    public static function id(): string
    {
        return 'static';
    }

    public static function label(): string
    {
        return 'Static';
    }

    public function webMode(): WebMode
    {
        return WebMode::STATIC;
    }

    public function detect(RepositoryFiles $files): ?array
    {
        return $files->has('index.html') && ! $files->has('package.json') && ! $files->has('composer.json') ? [] : null;
    }
}
