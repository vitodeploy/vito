<?php

return [

    'default' => env('QUEUE_CONNECTION', 'default'),

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => 600,
            'block_for' => null,
            'after_commit' => false,
        ],

        'default' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => 'default',
            'timeout' => 90,
            'retry_after' => 600,
            'block_for' => null,
            'after_commit' => false,
        ],

        'ssh' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => 'ssh',
            'timeout' => 1200,
            'retry_after' => max(3600, (int) env('BACKUP_RUN_TIMEOUT', 3600)) + 60,
            'block_for' => null,
            'after_commit' => false,
        ],

    ],

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'sqlite'),
        'table' => 'failed_jobs',
    ],

];
