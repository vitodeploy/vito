<?php

namespace App\SiteTypes;

use App\DTOs\DynamicField;
use App\Models\Site;
use App\Tooling\BunTooling;
use Illuminate\Validation\Rule;

class BunSite extends AbstractProxiedSiteType
{
    public static function id(): string
    {
        return 'bun';
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

    /**
     * @return array<int, DynamicField>
     */
    public static function formFields(): array
    {
        return array_merge(
            [
                DynamicField::make('bun_version')
                    ->toolingPicker(BunTooling::class),
            ],
            parent::sharedFormFields(),
        );
    }

    public function createRules(array $input): array
    {
        return array_merge(parent::createRules($input), [
            'bun_version' => [
                'required',
                Rule::in(BunTooling::supportedVersions()),
            ],
        ]);
    }

    public function data(array $input): array
    {
        return [
            'bun_version' => $input['bun_version'] ?? '1.2',
            'start_command' => ! empty($input['start_command']) ? $input['start_command'] : $this->defaultStartCommand(),
        ];
    }

    protected function deployCommands(): array
    {
        return [
            'bun install --frozen-lockfile',
            'bun run build',
        ];
    }

    protected function defaultStartCommand(): string
    {
        return 'bun run start';
    }
}
