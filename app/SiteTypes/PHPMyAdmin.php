<?php

namespace App\SiteTypes;

use App\Exceptions\SSHError;
use App\Models\Site;
use Illuminate\Validation\Rule;

class PHPMyAdmin extends PHPSite
{
    public static function id(): string
    {
        return 'phpmyadmin';
    }

    public static function make(): self
    {
        return new self(new Site(['type' => self::id()]));
    }

    public function createRules(array $input): array
    {
        return [
            'php_version' => [
                'required',
                Rule::in($this->site->server->installedPHPVersions()),
            ],
        ];
    }

    public function createFields(array $input): array
    {
        return [
            'web_directory' => '',
            'php_version' => $input['php_version'] ?? '',
        ];
    }

    public function data(array $input): array
    {
        return [
            'version' => '5.2.2',
        ];
    }

    /**
     * @throws SSHError
     */
    public function install(): void
    {
        $this->isolate();
        $this->site->webserver()->createVHost($this->site);
        $this->progress(30);
        $this->site->server->ssh($this->site->user)->exec(
            view('ssh.phpmyadmin.install', [
                'version' => $this->site->type_data['version'],
                'path' => $this->site->path,
            ]),
            'install-phpmyadmin',
            $this->site->id
        );
        $this->progress(65);
        $this->site->php()?->restart();
    }
}
