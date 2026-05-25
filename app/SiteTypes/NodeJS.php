<?php

namespace App\SiteTypes;

use App\Models\Site;
use App\Models\SourceControl;
use RuntimeException;

class NodeJS extends AbstractSiteType
{
    public static function id(): string
    {
        return 'nodejs';
    }

    public function language(): string
    {
        return 'nodejs';
    }

    public function requiredServices(): array
    {
        return [
            'nodejs',
            'webserver',
            'process_manager',
        ];
    }

    public static function make(): self
    {
        return new self(new Site(['type' => self::id()]));
    }

    public function createRules(array $input): array
    {
        return [
            'source_control' => SourceControl::siteValidationRules($this->site->server),
            'repository' => [
                'required',
            ],
            'branch' => [
                'required',
            ],
            'port' => [
                'required',
                'integer',
                'between:1024,65535',
            ],
        ];
    }

    public function createFields(array $input): array
    {
        return [
            'source_control_id' => $input['source_control'] ?? '',
            'repository' => $input['repository'] ?? '',
            'branch' => $input['branch'] ?? '',
            'port' => $input['port'] ?? '',
        ];
    }

    public function data(array $input): array
    {
        return [];
    }

    public function install(): void
    {
        throw new RuntimeException('The legacy "nodejs" site type is deprecated. Use the "node" site type instead.');
    }

    public function baseCommands(): array
    {
        return [
            [
                'name' => 'npm:install',
                'command' => 'npm install',
            ],
        ];
    }

    public function vhostData(): array
    {
        return [
            'is_reverse_proxy' => true,
        ];
    }
}
