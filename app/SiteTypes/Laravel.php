<?php

namespace App\SiteTypes;

class Laravel extends PHPSite
{
    public function baseCommands(): array
    {
        return array_merge(parent::baseCommands(), [
            // Initial Setup Commands
            [
                'name' => 'Generate Application Key',
                'command' => 'php artisan key:generate',
            ],
            [
                'name' => 'Create Storage Symbolic Link',
                'command' => 'php artisan storage:link',
            ],
            // Database Commands
            [
                'name' => 'Run Database Migrations',
                'command' => 'php artisan migrate --force',
            ],
            // Cache & Optimization Commands
            [
                'name' => 'Optimize Application',
                'command' => 'php artisan optimize',
            ],
            [
                'name' => 'Clear All Application Optimizations',
                'command' => 'php artisan optimize:clear',
            ],
            [
                'name' => 'Clear Application Cache Only',
                'command' => 'php artisan cache:clear',
            ],
            // Worker Commands
            [
                'name' => 'Restart Workers',
                'command' => 'php artisan queue:restart',
            ],
            [
                'name' => 'Clear All Failed Queue Jobs',
                'command' => 'php artisan queue:flush',
            ],
            // Application State Commands
            [
                'name' => 'Put Application in Maintenance Mode',
                'command' => 'php artisan down --retry=5 --refresh=6 --quiet',
            ],
            [
                'name' => 'Bring Application Online',
                'command' => 'php artisan up',
            ],
        ]);
    }
}
