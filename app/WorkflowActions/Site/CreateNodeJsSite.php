<?php

namespace App\WorkflowActions\Site;

class CreateNodeJsSite extends CreateSite
{
    public function inputs(): array
    {
        return array_merge(parent::inputs(), [
            'type' => 'nodejs',
            'port' => 'Port to run the Node.js application on, example: 3000',
            'source_control' => 'Source control ID',
            'repository' => 'organization/repository',
            'branch' => 'Branch to deploy, example: main',
        ]);
    }
}
