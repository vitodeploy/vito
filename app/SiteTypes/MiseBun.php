<?php

namespace App\SiteTypes;

use App\Models\Site;
use App\Models\SourceControl;
use App\Tooling\BunTooling;
use Illuminate\Validation\Rule;

class MiseBun extends AbstractProxiedSiteType
{
    public static function id(): string
    {
        return 'mise_bun';
    }

    public function language(): string
    {
        return 'bun';
    }

    public static function make(): self
    {
        return new self(new Site(['type' => self::id()]));
    }

    public static function createTimeTools(): array
    {
        return ['bun'];
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
                'numeric',
                'between:1,65535',
            ],
            'bun_version' => [
                'required',
                Rule::in(BunTooling::supportedVersions()),
            ],
            'build_command' => [
                'nullable',
                'string',
            ],
            'start_command' => [
                'nullable',
                'string',
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
        return [
            'bun_version' => $input['bun_version'] ?? '1.2',
            'build_command' => ! empty($input['build_command']) ? $input['build_command'] : 'bun run build',
            'start_command' => ! empty($input['start_command']) ? $input['start_command'] : 'bun run start',
        ];
    }

    protected function installCommand(): string
    {
        return 'bun install --frozen-lockfile';
    }

    protected function buildCommand(): string
    {
        return $this->site->type_data['build_command'] ?? 'bun run build';
    }

    protected function startCommand(): string
    {
        return $this->site->type_data['start_command'] ?? 'bun run start';
    }
}
