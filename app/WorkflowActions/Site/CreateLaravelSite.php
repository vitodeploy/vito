<?php

namespace App\WorkflowActions\Site;

use App\DTOs\DynamicField;
use App\SiteTypes\Laravel;

class CreateLaravelSite extends CreateSite
{
    protected function siteType(): string
    {
        return Laravel::id();
    }

    public function inputs(): array
    {
        return [
            ...parent::inputs(),
            $this->phpVersionField(),
            ...$this->repositoryFields(),
            $this->webDirectoryField()->default('public'),
            DynamicField::make('composer')->checkbox()->label('Run composer install')->default(true),
        ];
    }
}
