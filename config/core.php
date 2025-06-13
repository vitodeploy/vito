<?php

use App\Enums\OperatingSystem;
use App\Enums\SslType;
use App\Enums\UserRole;
use App\Models\Server;
use App\Models\Site;

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
