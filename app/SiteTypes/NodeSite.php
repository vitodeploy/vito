<?php

namespace App\SiteTypes;

use App\DTOs\DynamicField;
use App\Enums\NodePackageManager;
use App\Models\Site;
use App\Tooling\BunTooling;
use App\Tooling\NodeTooling;
use App\Tooling\PnpmTooling;
use App\Tooling\ToolingRegistry;
use App\Tooling\YarnTooling;
use Illuminate\Validation\Rule;

class NodeSite extends AbstractProxiedSiteType
{
    public static function id(): string
    {
        return 'node';
    }

    public function language(): string
    {
        return 'nodejs';
    }

    public static function make(): self
    {
        return new self(new Site(['type' => self::id()]));
    }

    public static function createTimeTools(): array
    {
        return ['node', 'pnpm', 'yarn'];
    }

    /**
     * @return array<int, DynamicField>
     */
    public static function formFields(): array
    {
        return array_merge(
            [
                DynamicField::make('node_version')
                    ->toolingPicker(NodeTooling::class),
                DynamicField::make('package_manager')
                    ->toolingSelector(
                        [NodeTooling::class, PnpmTooling::class, YarnTooling::class],
                        [NodeTooling::class => 'npm'],
                    )
                    ->label('Package Manager'),
            ],
            parent::sharedFormFields(),
        );
    }

    public function createRules(array $input): array
    {
        $rules = array_merge(parent::createRules($input), [
            'node_version' => [
                'required',
                Rule::in(NodeTooling::supportedVersions()),
            ],
            'package_manager' => [
                'required',
                Rule::in(NodePackageManager::toolIds()),
            ],
        ]);

        $pmToolId = $input['package_manager'] ?? null;
        if (is_string($pmToolId) && $pmToolId !== 'node') {
            $tool = ToolingRegistry::find($pmToolId);
            if ($tool !== null) {
                $rules[$pmToolId.'_version'] = [
                    'required',
                    Rule::in($tool::supportedVersions()),
                ];
            }
        }

        return $rules;
    }

    public function data(array $input): array
    {
        $pmToolId = $input['package_manager'] ?? 'node';
        try {
            $packageManager = NodePackageManager::fromToolId($pmToolId);
        } catch (\InvalidArgumentException) {
            $packageManager = NodePackageManager::Npm;
            $pmToolId = 'node';
        }

        $data = [
            'node_version' => $input['node_version'] ?? '22',
            'package_manager' => $packageManager->value,
            'start_command' => ! empty($input['start_command']) ? $input['start_command'] : $this->defaultStartCommand($packageManager),
        ];

        foreach ([BunTooling::id(), PnpmTooling::id(), YarnTooling::id()] as $managedId) {
            if ($pmToolId === $managedId) {
                $data[$managedId.'_version'] = $input[$managedId.'_version'] ?? 'none';
            }
        }

        return $data;
    }

    protected function packageManager(): NodePackageManager
    {
        $value = $this->site->type_data['package_manager'] ?? NodePackageManager::Npm->value;

        return NodePackageManager::from($value);
    }

    protected function deployCommands(): array
    {
        $packageManager = $this->packageManager();

        return [
            $packageManager->installCommand(),
            $packageManager->buildCommand(),
        ];
    }

    protected function defaultStartCommand(?NodePackageManager $packageManager = null): string
    {
        return ($packageManager ?? $this->packageManager())->startCommand();
    }
}
