<?php

namespace App\SiteTypes;

class Laravel extends PHPSite
{
    public function predefinedCommands(): array
    {
        return [
            // Initial Setup Commands
            [
                'name' => 'Install Composer Dependencies',
                'command' => 'composer install --no-dev --no-interaction --no-progress',
            ],
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

            // Queue Commands
            [
                'name' => 'Restart Queue Workers',
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
        ];
    }
}
