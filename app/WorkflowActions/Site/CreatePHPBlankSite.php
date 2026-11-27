<?php

namespace App\WorkflowActions\Site;

use App\SiteTypes\PHPBlank;

class CreatePHPBlankSite extends CreateSite
{
    protected function siteType(): string
    {
        return PHPBlank::id();
    }

    public function inputs(): array
    {
        return [
            ...parent::inputs(),
            $this->phpVersionField(),
            $this->webDirectoryField(),
        ];
    }
}
