<?php

namespace App\WorkflowActions\Site;

class CreatePHPMyAdminSite extends CreateSite
{
    public function inputs(): array
    {
        return array_merge(parent::inputs(), [
            'type' => 'phpmyadmin',
            'php_version' => 'PHP version, example: 8.1, 8.2',
        ]);
    }
}
