<?php

namespace App\SiteTypes;

use App\DTOs\DynamicField;
use App\DTOs\DynamicForm;
use App\Exceptions\SSHError;
use App\Models\Site;
use Illuminate\Validation\Rule;

class PHPBlank extends PHPSite
{
    public static function make(): self
    {
        return new self(new Site(['type' => \App\Enums\SiteType::PHP]));
    }

    public function fields(): DynamicForm
    {
        return new DynamicForm([
            DynamicField::make('php_version')
                ->component()
                ->label('PHP Version'),
            DynamicField::make('web_directory')
                ->text()
                ->label('Web Directory')
                ->placeholder('For / leave empty')
                ->description('The relative path of your website from /home/vito/your-domain/'),
        ]);
    }

    public function createRules(array $input): array
    {
        return [
            'web_directory' => [
                'nullable',
                'string',
                'max:255',
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
            'web_directory' => $input['web_directory'] ?? '',
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
        $this->isolate();
        $this->site->webserver()->createVHost($this->site);
        $this->progress(65);
        $this->site->php()?->restart();
    }

    public function baseCommands(): array
    {
        return [];
    }
}
