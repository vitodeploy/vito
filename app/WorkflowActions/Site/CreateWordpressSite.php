<?php

namespace App\WorkflowActions\Site;

use App\DTOs\DynamicField;
use App\SiteTypes\Wordpress;

class CreateWordpressSite extends CreateSite
{
    protected function siteType(): string
    {
        return Wordpress::id();
    }

    public function inputs(): array
    {
        return [
            ...parent::inputs(),
            $this->phpVersionField(),
            DynamicField::make('title')->text()->label('Site Title'),
            DynamicField::make('username')->text()->label('Admin Username'),
            DynamicField::make('password')->passwordWithToggle()->label('Admin Password'),
            DynamicField::make('email')->text()->label('Admin Email'),
            DynamicField::make('database')->resource('database', 'server_id')->label('Database'),
            DynamicField::make('database_user')->resource('database_user', 'server_id')->label('Database User'),
        ];
    }
}
