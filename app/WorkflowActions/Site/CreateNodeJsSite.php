<?php

namespace App\WorkflowActions\Site;

use App\DTOs\DynamicField;
use App\SiteTypes\NodeSite;

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
            DynamicField::make('node_version')->text()->label('Node.js Version')->placeholder('22'),
            DynamicField::make('package_manager')->select()->label('Package Manager')->options(['node', 'pnpm', 'yarn'])->default('node'),
            DynamicField::make('port')->text()->label('Port')->placeholder('3000'),
            ...$this->repositoryFields(),
        ];
    }
}
