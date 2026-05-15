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

    /*
     * Linux user names that must never be used as a site's isolated user.
     * Combined with the per-server SSH user to block reuse of privileged or
     * service accounts when validating site creation.
     */
    'reserved_user_names' => [
        'root', 'daemon', 'bin', 'sys', 'sync', 'games', 'man', 'lp', 'mail',
        'news', 'uucp', 'proxy', 'www-data', 'backup', 'list', 'irc', 'gnats',
        'nobody', 'systemd-network', 'systemd-resolve', 'systemd-timesync',
        'syslog', 'messagebus', '_apt', 'sshd', 'tcpdump', 'tss', 'landscape',
        'pollinate', 'fwupd-refresh', 'mysql', 'postgres', 'redis', 'mongodb',
        'memcached', 'rabbitmq', 'nginx', 'apache', 'caddy', 'ubuntu', 'debian',
        'admin', 'administrator', 'vito',
    ],
    'ssh_public_key_name' => env('SSH_PUBLIC_KEY_NAME', 'ssh-public.key'),
    'ssh_private_key_name' => env('SSH_PRIVATE_KEY_NAME', 'ssh-private.pem'),
    'logs_disk' => env('SERVER_LOGS_DISK', 'server-logs'), // should be FilesystemAdapter storage
    'key_pairs_disk' => env('KEY_PAIRS_DISK', 'key-pairs'), // should be FilesystemAdapter storage

    /*
     * WebSocket
     */
    'ws_host' => env('WS_HOST', '127.0.0.1'),
    'ws_port' => env('WS_PORT', '8085'),
    'ws_broadcast_secret' => env('WS_BROADCAST_SECRET', env('APP_KEY')),
    'ws_broadcast_secret_set' => ! empty(env('WS_BROADCAST_SECRET')),
    'ws_allowed_origins' => array_filter(array_map('trim', explode(',', env('WS_ALLOWED_ORIGINS', '')))),

    /*
     * General
     */
    'operating_systems' => [
        OperatingSystem::UBUNTU20->value,
        OperatingSystem::UBUNTU22->value,
        OperatingSystem::UBUNTU24->value,
    ],

    /*
     * Disable these IPs for servers
     */
    'restricted_ip_addresses' => array_merge(
        ['127.0.0.1', 'localhost', '0.0.0.0'],
        explode(',', (string) env('RESTRICTED_IP_ADDRESSES', ''))
    ),

    'ssl_types' => [
        SslType::CSR->value,
        SslType::CUSTOM->value,
        SslType::LETSENCRYPT->value,
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
