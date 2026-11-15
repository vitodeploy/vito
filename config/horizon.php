<?php

use App\Http\Middleware\MustBeAdminMiddleware;
use Illuminate\Support\Str;

return [

    'domain' => env('HORIZON_DOMAIN'),

    'path' => env('HORIZON_PATH', 'horizon'),

    'use' => 'default',

    'prefix' => env(
        'HORIZON_PREFIX',
        Str::slug(env('APP_NAME', 'laravel'), '_').'_horizon:'
    ),

    'middleware' => [
        'web',
        MustBeAdminMiddleware::class,
    ],

    'waits' => [
        'redis:default' => 60,
    ],

    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    'silenced' => [
    ],

    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,
            'queue' => 24,
        ],
    ],

    'fast_termination' => false,

    'memory_limit' => env('HORIZON_MEMORY_LIMIT', 64),

    'defaults' => [
        'default' => [
            'connection' => 'redis',
            'queue' => ['default'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => env('HORIZON_DEFAULT_MAX_PROCESSES', 1),
            'maxTime' => env('HORIZON_DEFAULT_MAX_TIME', 0),
            'maxJobs' => env('HORIZON_DEFAULT_MAX_JOBS', 0),
            'memory' => env('HORIZON_DEFAULT_MEMORY', 128),
            'tries' => env('HORIZON_DEFAULT_TRIES', 1),
            'timeout' => env('HORIZON_DEFAULT_TIMEOUT', 90),
            'nice' => env('HORIZON_DEFAULT_NICE', 0),
        ],

        'ssh' => [
            'connection' => 'ssh',
            'queue' => ['ssh'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => env('HORIZON_SSH_MAX_PROCESSES', 1),
            'maxTime' => env('HORIZON_SSH_MAX_TIME', 0),
            'maxJobs' => env('HORIZON_SSH_MAX_JOBS', 0),
            'memory' => env('HORIZON_SSH_MEMORY', 128),
            'tries' => env('HORIZON_SSH_TRIES', 1),
            'timeout' => env('HORIZON_SSH_TIMEOUT', max(3600, (int) env('BACKUP_RUN_TIMEOUT', 3600)) + 30),
            'nice' => env('HORIZON_SSH_NICE', 0),
        ],

        'ssh-certbot' => [
            'connection' => 'redis',
            'queue' => ['ssh-certbot'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => env('HORIZON_SSH_CERTBOT_MAX_PROCESSES', 1),
            'maxTime' => env('HORIZON_SSH_CERTBOT_MAX_TIME', 0),
            'maxJobs' => env('HORIZON_SSH_CERTBOT_MAX_JOBS', 0),
            'memory' => env('HORIZON_SSH_CERTBOT_MEMORY', 128),
            'tries' => env('HORIZON_SSH_CERTBOT_TRIES', 1),
            'timeout' => env('HORIZON_SSH_CERTBOT_TIMEOUT', 600),
            'nice' => env('HORIZON_SSH_CERTBOT_NICE', 0),
        ],
    ],

    'environments' => [
        '*' => [
            'default' => [
                'maxProcesses' => env('HORIZON_DEFAULT_MAX_PROCESSES', 3),
            ],
            'ssh' => [
                'maxProcesses' => env('HORIZON_SSH_MAX_PROCESSES', 3),
            ],
            'ssh-certbot' => [
                'maxProcesses' => env('HORIZON_SSH_CERTBOT_MAX_PROCESSES', 1),
            ],
        ],
    ],
];
