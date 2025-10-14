<?php

namespace App\WorkflowActions\Site;

class CreateWordpressSite extends CreateSite
{
    public function inputs(): array
    {
        return array_merge(parent::inputs(), [
            'type' => 'wordpress',
            'php_version' => 'PHP version, example: 8.1, 8.2',
            'title' => 'WordPress Site Title',
            'username' => 'WordPress Admin Username',
            'password' => 'WordPress Admin Password',
            'email' => 'WordPress Admin Email',
            'database' => 'Database ID',
            'database_user' => 'Database User ID',
        ]);
    }
}
