<?php

namespace App\SiteTypes;

use App\Exceptions\FailedToDeployGitKey;
use App\Exceptions\SSHError;
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

    /**
     * @throws FailedToDeployGitKey
     * @throws SSHError
     */
    public function install(): void
    {
        parent::install();

        $envPath = $this->site->type_data['env_path'] ?? $this->site->path.'/.env';
        $examplePath = $this->site->path.'/.env.example';

        $this->site->server->ssh($this->site->user)->exec(
            view('ssh.laravel.ensure-env', [
                'envPath' => $envPath,
                'examplePath' => $examplePath,
            ]),
            'ensure-env',
            $this->site->id,
        );
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
}
