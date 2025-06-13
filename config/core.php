<?php

use App\Enums\OperatingSystem;
use App\Enums\ServerType;
use App\Enums\SslType;
use App\Enums\UserRole;
use App\Enums\Webserver;
use App\Models\Server;
use App\Models\Site;
use App\ServerTypes\Database;
use App\ServerTypes\Regular;
use App\Services\PHP\PHP;

return [
    /*
     * SSH
     */
    'ssh_user' => env('SSH_USER', 'vito'),
    'ssh_public_key_name' => env('SSH_PUBLIC_KEY_NAME', 'ssh-public.key'),
    'ssh_private_key_name' => env('SSH_PRIVATE_KEY_NAME', 'ssh-private.pem'),
    'logs_disk' => env('SERVER_LOGS_DISK', 'server-logs'), // should be FilesystemAdapter storage
    'key_pairs_disk' => env('KEY_PAIRS_DISK', 'key-pairs'), // should be FilesystemAdapter storage

    /*
     * General
     */
    'operating_systems' => [
        OperatingSystem::UBUNTU20,
        OperatingSystem::UBUNTU22,
        OperatingSystem::UBUNTU24,
    ],
    'operating_system_versions' => [
        OperatingSystem::UBUNTU20 => '20.04',
        OperatingSystem::UBUNTU22 => '22.04',
        OperatingSystem::UBUNTU24 => '24.04',
    ],
    'webservers' => [
        Webserver::NONE,
        Webserver::NGINX,
        Webserver::CADDY,
    ],
    'php_versions' => [
        \App\Enums\PHP::NONE,
        \App\Enums\PHP::V70,
        \App\Enums\PHP::V71,
        \App\Enums\PHP::V72,
        \App\Enums\PHP::V73,
        \App\Enums\PHP::V74,
        \App\Enums\PHP::V80,
        \App\Enums\PHP::V81,
        \App\Enums\PHP::V82,
        \App\Enums\PHP::V83,
        \App\Enums\PHP::V84,
    ],
    'nodejs_versions' => [
        \App\Enums\NodeJS::NONE,
        \App\Enums\NodeJS::V4,
        \App\Enums\NodeJS::V6,
        \App\Enums\NodeJS::V8,
        \App\Enums\NodeJS::V10,
        \App\Enums\NodeJS::V12,
        \App\Enums\NodeJS::V14,
        \App\Enums\NodeJS::V16,
        \App\Enums\NodeJS::V18,
        \App\Enums\NodeJS::V20,
        \App\Enums\NodeJS::V22,
    ],
    'databases' => [
        \App\Enums\Database::NONE,
        \App\Enums\Database::MYSQL57,
        \App\Enums\Database::MYSQL80,
        \App\Enums\Database::MYSQL84,
        \App\Enums\Database::MARIADB103,
        \App\Enums\Database::MARIADB104,
        \App\Enums\Database::MARIADB106,
        \App\Enums\Database::MARIADB1011,
        \App\Enums\Database::MARIADB114,
        \App\Enums\Database::POSTGRESQL12,
        \App\Enums\Database::POSTGRESQL13,
        \App\Enums\Database::POSTGRESQL14,
        \App\Enums\Database::POSTGRESQL15,
        \App\Enums\Database::POSTGRESQL16,
    ],
    'databases_name' => [
        \App\Enums\Database::NONE => 'none',
        \App\Enums\Database::MYSQL57 => 'mysql',
        \App\Enums\Database::MYSQL80 => 'mysql',
        \App\Enums\Database::MYSQL84 => 'mysql',
        \App\Enums\Database::MARIADB103 => 'mariadb',
        \App\Enums\Database::MARIADB104 => 'mariadb',
        \App\Enums\Database::MARIADB106 => 'mariadb',
        \App\Enums\Database::MARIADB1011 => 'mariadb',
        \App\Enums\Database::MARIADB114 => 'mariadb',
        \App\Enums\Database::POSTGRESQL12 => 'postgresql',
        \App\Enums\Database::POSTGRESQL13 => 'postgresql',
        \App\Enums\Database::POSTGRESQL14 => 'postgresql',
        \App\Enums\Database::POSTGRESQL15 => 'postgresql',
        \App\Enums\Database::POSTGRESQL16 => 'postgresql',
    ],
    'databases_version' => [
        \App\Enums\Database::NONE => '',
        \App\Enums\Database::MYSQL57 => '5.7',
        \App\Enums\Database::MYSQL80 => '8.0',
        \App\Enums\Database::MYSQL84 => '8.4',
        \App\Enums\Database::MARIADB103 => '10.3',
        \App\Enums\Database::MARIADB104 => '10.4',
        \App\Enums\Database::MARIADB106 => '10.6',
        \App\Enums\Database::MARIADB1011 => '10.11',
        \App\Enums\Database::MARIADB114 => '11.4',
        \App\Enums\Database::POSTGRESQL12 => '12',
        \App\Enums\Database::POSTGRESQL13 => '13',
        \App\Enums\Database::POSTGRESQL14 => '14',
        \App\Enums\Database::POSTGRESQL15 => '15',
        \App\Enums\Database::POSTGRESQL16 => '16',
    ],
    'database_features' => [
        'remote' => [
            'mysql',
            'mariadb',
        ],
    ],

    /*
     * Server
     */
    'server_types' => [
        ServerType::REGULAR,
        ServerType::DATABASE,
    ],
    'server_types_class' => [
        ServerType::REGULAR => Regular::class,
        ServerType::DATABASE => Database::class,
    ],

    /*
     * available php extensions
     */
    'php_extensions' => [
        'imagick',
        'exif',
        'gmagick',
        'gmp',
        'intl',
        'sqlite3',
        'opcache',
    ],

    /*
     * php settings
     */
    'php_settings' => [
        'upload_max_filesize' => '2',
        'memory_limit' => '128',
        'max_execution_time' => '30',
        'post_max_size' => '2',
    ],
    'php_settings_unit' => [
        'upload_max_filesize' => 'M',
        'memory_limit' => 'M',
        'max_execution_time' => 'S',
        'post_max_size' => 'M',
    ],

    /*
     * Disable these IPs for servers
     */
    'restricted_ip_addresses' => array_merge(
        ['127.0.0.1', 'localhost', '0.0.0.0'],
        explode(',', (string) env('RESTRICTED_IP_ADDRESSES', ''))
    ),

    'ssl_types' => [
        SslType::LETSENCRYPT,
        SslType::CUSTOM,
    ],

    'metrics_data_retention' => [
        7,
        14,
        30,
        90,
    ],

    'taggable_types' => [
        Server::class,
        Site::class,
    ],

    'user_roles' => [
        UserRole::USER,
        UserRole::ADMIN,
    ],

    'cronjob_intervals' => [
        '* * * * *' => 'Every Minute',
        '0 * * * *' => 'Hourly',
        '0 0 * * *' => 'Daily',
        '0 0 * * 0' => 'Weekly',
        '0 0 1 * *' => 'Monthly',
        'custom' => 'Custom',
    ],

    'colors' => [
        'slate',
        'gray',
        'red',
        'orange',
        'amber',
        'yellow',
        'lime',
        'green',
        'emerald',
        'teal',
        'cyan',
        'sky',
        'blue',
        'indigo',
        'violet',
        'purple',
        'fuchsia',
        'pink',
        'rose',
    ],

    'metrics_periods' => [
        '10m',
        '30m',
        '1h',
        '12h',
        '1d',
        '7d',
        'custom',
    ],
];
