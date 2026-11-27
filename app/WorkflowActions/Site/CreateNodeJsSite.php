<?php

namespace App\WorkflowActions\Site;

use App\DTOs\DynamicField;
use App\SiteTypes\NodeSite;
use App\Tooling\NodeTooling;
use App\Tooling\PnpmTooling;
use App\Tooling\YarnTooling;

class CreateNodeJsSite extends CreateSite
{
    protected function siteType(): string
    {
        return NodeSite::id();
    }

    public function inputs(): array
    {
        return [
            ...parent::inputs(),
            DynamicField::make('node_version')->select()->label('Node.js Version')->options(NodeTooling::supportedVersions())->default(NodeTooling::supportedVersions()[0] ?? null),
            DynamicField::make('package_manager')->select()->label('Package Manager')->options(['node', 'pnpm', 'yarn'])->default('node'),
            DynamicField::make('pnpm_version')->select()->label('pnpm Version')->options(PnpmTooling::supportedVersions())->default(PnpmTooling::supportedVersions()[0] ?? null)->description('Used when the package manager is pnpm')->half(),
            DynamicField::make('yarn_version')->select()->label('Yarn Version')->options(YarnTooling::supportedVersions())->default(YarnTooling::supportedVersions()[0] ?? null)->description('Used when the package manager is yarn')->half(),
            DynamicField::make('port')->text()->label('Port')->placeholder('3000'),
            ...$this->repositoryFields(),
        ];
    }
}
