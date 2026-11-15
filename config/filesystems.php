<?php

return [

    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

        'key-pairs' => [
            'driver' => 'local',
            'root' => storage_path('app/key-pairs'),
        ],

        'tmp' => [
            'driver' => 'local',
            'root' => sys_get_temp_dir(),
        ],

        /** @deprecated */
        'key-pairs-local' => [
            'driver' => 'local',
            'root' => storage_path('app/key-pairs'),
        ],

        'server-logs' => [
            'driver' => 'local',
            'root' => storage_path('app/server-logs'),
        ],

        /** @deprecated */
        'server-logs-local' => [
            'driver' => 'local',
            'root' => storage_path('app/server-logs'),
        ],

        'app-logs' => [
            'driver' => 'local',
            'root' => storage_path('app/logs'),
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
