<?php

namespace App\WorkflowActions\Site;

use App\SiteTypes\PHPMyAdmin;

class CreatePHPMyAdminSite extends CreateSite
{
    protected function siteType(): string
    {
        return PHPMyAdmin::id();
    }

    public function inputs(): array
    {
        return [
            ...parent::inputs(),
            $this->phpVersionField(),
        ];
    }
}
