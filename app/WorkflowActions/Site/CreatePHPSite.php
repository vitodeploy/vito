<?php

namespace App\WorkflowActions\Site;

class CreatePHPSite extends CreateSite
{
    public function inputs(): array
    {
        return array_merge(parent::inputs(), [
            'type' => 'php',
            'php_version' => 'PHP version, example: 8.1, 8.2',
            'source_control' => 'Source control ID',
            'repository' => 'organization/repository',
            'branch' => 'Branch to deploy, example: main',
            'web_directory' => 'Web directory, example: public (optional)',
            'composer' => 'whether to run composer install (true/false, optional)',
        ]);
    }
}
