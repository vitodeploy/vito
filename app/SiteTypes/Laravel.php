<?php

namespace App\SiteTypes;

use App\Models\Site;

class Laravel extends PHPSite
{
    public static function id(): string
    {
        return 'laravel';
    }

    public static function make(): self
    {
        return new self(new Site(['type' => self::id()]));
    }

    public function baseCommands(): array
    {
        return array_merge(parent::baseCommands(), [
            [
                'name' => 'cache:clear',
                'command' => 'php artisan cache:clear',
            ],
            [
                'name' => 'down',
                'command' => 'php artisan down --retry=5 --refresh=6 --quiet',
            ],
            [
                'name' => 'up',
                'command' => 'php artisan up',
            ],
        ]);
    }

    public function install(): void
    {
        parent::install();

        $this->site->server->ssh($this->site->user)->exec(
            __('cp :path/.env.example :path/.env', ['path' => $this->site->path])
        );
        $this->site->server->ssh($this->site->user)->exec(
            __('php :path/artisan key:generate', ['path' => $this->site->path])
        );
    }
}
