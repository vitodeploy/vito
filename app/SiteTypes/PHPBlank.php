<?php

namespace App\SiteTypes;

use App\Exceptions\SSHError;
use App\Models\Site;
use App\Traits\NormalizesWebDirectory;
use Illuminate\Validation\Rule;

class PHPBlank extends PHPSite
{
    use NormalizesWebDirectory;

    public static function id(): string
    {
        return 'php-blank';
    }

    public static function make(): self
    {
        return new self(new Site(['type' => self::id()]));
    }

    public function createRules(array $input): array
    {
        return [
            'web_directory' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9._\-\/]+$/',
                'not_regex:/\.\./',
            ],
            'php_version' => [
                'required',
                Rule::in($this->site->server->installedPHPVersions()),
            ],
        ];
    }

    public function createFields(array $input): array
    {
        return [
            'web_directory' => $this->normalizeWebDirectory($input['web_directory'] ?? ''),
            'php_version' => $input['php_version'] ?? '',
        ];
    }

    public function data(array $input): array
    {
        return [];
    }

    /**
     * @throws SSHError
     */
    public function install(): void
    {
        $this->progress(0, 'isolating-user');
        $this->isolate();
        $this->progress(20, 'creating-vhost');
        $this->site->webserver()->createVHost($this->site);
        $this->progress(55, 'restarting-php');
        $this->site->php()?->restart();
        $this->progress(90, 'finishing');
    }

    public function baseCommands(): array
    {
        return [];
    }
}
