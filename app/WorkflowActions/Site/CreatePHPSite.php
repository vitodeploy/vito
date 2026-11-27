<?php

namespace App\WorkflowActions\Site;

use App\DTOs\DynamicField;
use App\SiteTypes\PHPSite;

class CreatePHPSite extends CreateSite
{
    protected function siteType(): string
    {
        return PHPSite::id();
    }

    public function inputs(): array
    {
        return [
            ...parent::inputs(),
            $this->phpVersionField(),
            ...$this->repositoryFields(),
            $this->webDirectoryField(),
            DynamicField::make('composer')->checkbox()->label('Run composer install')->default(false),
        ];
    }
}
