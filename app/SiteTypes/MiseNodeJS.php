<?php

namespace App\SiteTypes;

use App\Enums\NodePackageManager;
use App\Models\Site;
use App\Models\SourceControl;
use App\Tooling\BunTooling;
use App\Tooling\NodeTooling;
use App\Tooling\PnpmTooling;
use App\Tooling\ToolingRegistry;
use App\Tooling\YarnTooling;
use Illuminate\Validation\Rule;

class MiseNodeJS extends AbstractProxiedSiteType
{
    public static function id(): string
    {
        return 'mise_nodejs';
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

    public function createRules(array $input): array
    {
        $rules = [
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
            'node_version' => [
                'required',
                Rule::in(NodeTooling::supportedVersions()),
            ],
            'package_manager' => [
                'required',
                Rule::in(NodePackageManager::toolIds()),
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
            'build_command' => ! empty($input['build_command']) ? $input['build_command'] : $packageManager->buildCommand(),
            'start_command' => ! empty($input['start_command']) ? $input['start_command'] : $packageManager->startCommand(),
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

    protected function installCommand(): string
    {
        return $this->packageManager()->installCommand();
    }

    protected function buildCommand(): string
    {
        return $this->site->type_data['build_command'] ?? $this->packageManager()->buildCommand();
    }

    protected function startCommand(): string
    {
        return $this->site->type_data['start_command'] ?? $this->packageManager()->startCommand();
    }
}
