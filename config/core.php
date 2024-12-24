<?php

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
        \App\Enums\OperatingSystem::UBUNTU20,
        \App\Enums\OperatingSystem::UBUNTU22,
        \App\Enums\OperatingSystem::UBUNTU24,
    ],
    'operating_system_versions' => [
        \App\Enums\OperatingSystem::UBUNTU20 => '20.04',
        \App\Enums\OperatingSystem::UBUNTU22 => '22.04',
        \App\Enums\OperatingSystem::UBUNTU24 => '24.04',
    ],
    'webservers' => [
        \App\Enums\Webserver::NONE,
        \App\Enums\Webserver::NGINX,
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
        \App\Enums\ServerType::REGULAR,
        \App\Enums\ServerType::DATABASE,
    ],
    'server_types_class' => [
        \App\Enums\ServerType::REGULAR => \App\ServerTypes\Regular::class,
        \App\Enums\ServerType::DATABASE => \App\ServerTypes\Database::class,
    ],
    'server_providers' => [
        \App\Enums\ServerProvider::CUSTOM,
        \App\Enums\ServerProvider::AWS,
        \App\Enums\ServerProvider::LINODE,
        \App\Enums\ServerProvider::DIGITALOCEAN,
        \App\Enums\ServerProvider::VULTR,
        \App\Enums\ServerProvider::HETZNER,
    ],
    'server_providers_class' => [
        \App\Enums\ServerProvider::CUSTOM => \App\ServerProviders\Custom::class,
        \App\Enums\ServerProvider::AWS => \App\ServerProviders\AWS::class,
        \App\Enums\ServerProvider::LINODE => \App\ServerProviders\Linode::class,
        \App\Enums\ServerProvider::DIGITALOCEAN => \App\ServerProviders\DigitalOcean::class,
        \App\Enums\ServerProvider::VULTR => \App\ServerProviders\Vultr::class,
        \App\Enums\ServerProvider::HETZNER => \App\ServerProviders\Hetzner::class,
    ],
    'server_providers_default_user' => [
        'custom' => [
            \App\Enums\OperatingSystem::UBUNTU20 => 'root',
            \App\Enums\OperatingSystem::UBUNTU22 => 'root',
            \App\Enums\OperatingSystem::UBUNTU24 => 'root',
        ],
        'aws' => [
            \App\Enums\OperatingSystem::UBUNTU20 => 'ubuntu',
            \App\Enums\OperatingSystem::UBUNTU22 => 'ubuntu',
            \App\Enums\OperatingSystem::UBUNTU24 => 'ubuntu',
        ],
        'linode' => [
            \App\Enums\OperatingSystem::UBUNTU20 => 'root',
            \App\Enums\OperatingSystem::UBUNTU22 => 'root',
            \App\Enums\OperatingSystem::UBUNTU24 => 'root',
        ],
        'digitalocean' => [
            \App\Enums\OperatingSystem::UBUNTU20 => 'root',
            \App\Enums\OperatingSystem::UBUNTU22 => 'root',
            \App\Enums\OperatingSystem::UBUNTU24 => 'root',
        ],
        'vultr' => [
            \App\Enums\OperatingSystem::UBUNTU20 => 'root',
            \App\Enums\OperatingSystem::UBUNTU22 => 'root',
            \App\Enums\OperatingSystem::UBUNTU24 => 'root',
        ],
        'hetzner' => [
            \App\Enums\OperatingSystem::UBUNTU20 => 'root',
            \App\Enums\OperatingSystem::UBUNTU22 => 'root',
            \App\Enums\OperatingSystem::UBUNTU24 => 'root',
        ],
    ],

    /*
     * Service
     */
    'service_types' => [
        'nginx' => 'webserver',
        'mysql' => 'database',
        'mariadb' => 'database',
        'postgresql' => 'database',
        'redis' => 'memory_database',
        'php' => 'php',
        'nodejs' => 'nodejs',
        'ufw' => 'firewall',
        'supervisor' => 'process_manager',
        'vito-agent' => 'monitoring',
        'remote-monitor' => 'monitoring',
    ],
    'service_handlers' => [
        'nginx' => \App\SSH\Services\Webserver\Nginx::class,
        'mysql' => \App\SSH\Services\Database\Mysql::class,
        'mariadb' => \App\SSH\Services\Database\Mariadb::class,
        'postgresql' => \App\SSH\Services\Database\Postgresql::class,
        'redis' => \App\SSH\Services\Redis\Redis::class,
        'php' => \App\SSH\Services\PHP\PHP::class,
        'nodejs' => \App\SSH\Services\NodeJS\NodeJS::class,
        'ufw' => \App\SSH\Services\Firewall\Ufw::class,
        'supervisor' => \App\SSH\Services\ProcessManager\Supervisor::class,
        'vito-agent' => \App\SSH\Services\Monitoring\VitoAgent\VitoAgent::class,
        'remote-monitor' => \App\SSH\Services\Monitoring\RemoteMonitor\RemoteMonitor::class,
    ],
    'service_versions' => [
        'nginx' => [
            'latest',
        ],
        'mysql' => [
            '5.7',
            '8.0',
            '8.4',
        ],
        'mariadb' => [
            '10.3',
            '10.4',
            '10.6',
            '10.11',
            '11.4',
        ],
        'postgresql' => [
            '12',
            '13',
            '14',
            '15',
            '16',
        ],
        'redis' => [
            'latest',
        ],
        'nodejs' => [
            '4',
            '6',
            '8',
            '10',
            '12',
            '14',
            '16',
            '18',
            '20',
            '22',
        ],
        'php' => [
            '5.6',
            '7.0',
            '7.1',
            '7.2',
            '7.3',
            '7.4',
            '8.0',
            '8.1',
            '8.2',
            '8.3',
            '8.4',
        ],
        'ufw' => [
            'latest',
        ],
        'supervisor' => [
            'latest',
        ],
        'vito-agent' => [
            'latest',
        ],
        'remote-monitor' => [
            'latest',
        ],
    ],
    'service_units' => [
        'nginx' => [
            \App\Enums\OperatingSystem::UBUNTU20 => [
                'latest' => 'nginx',
            ],
            \App\Enums\OperatingSystem::UBUNTU22 => [
                'latest' => 'nginx',
            ],
            \App\Enums\OperatingSystem::UBUNTU24 => [
                'latest' => 'nginx',
            ],
        ],
        'mysql' => [
            \App\Enums\OperatingSystem::UBUNTU20 => [
                '5.7' => 'mysql',
                '8.0' => 'mysql',
                '8.4' => 'mysql',
            ],
            \App\Enums\OperatingSystem::UBUNTU22 => [
                '5.7' => 'mysql',
                '8.0' => 'mysql',
                '8.4' => 'mysql',
            ],
            \App\Enums\OperatingSystem::UBUNTU24 => [
                '5.7' => 'mysql',
                '8.0' => 'mysql',
                '8.4' => 'mysql',
            ],
        ],
        'mariadb' => [
            \App\Enums\OperatingSystem::UBUNTU20 => [
                '10.3' => 'mariadb',
                '10.4' => 'mariadb',
                '10.6' => 'mariadb',
                '10.11' => 'mariadb',
                '11.4' => 'mariadb',
            ],
            \App\Enums\OperatingSystem::UBUNTU22 => [
                '10.3' => 'mariadb',
                '10.4' => 'mariadb',
                '10.6' => 'mariadb',
                '10.11' => 'mariadb',
                '11.4' => 'mariadb',
            ],
            \App\Enums\OperatingSystem::UBUNTU24 => [
                '10.3' => 'mariadb',
                '10.4' => 'mariadb',
                '10.6' => 'mariadb',
                '10.11' => 'mariadb',
                '11.4' => 'mariadb',
            ],
        ],
        'postgresql' => [
            \App\Enums\OperatingSystem::UBUNTU20 => [
                '12' => 'postgresql',
                '13' => 'postgresql',
                '14' => 'postgresql',
                '15' => 'postgresql',
                '16' => 'postgresql',
            ],
            \App\Enums\OperatingSystem::UBUNTU22 => [
                '12' => 'postgresql',
                '13' => 'postgresql',
                '14' => 'postgresql',
                '15' => 'postgresql',
                '16' => 'postgresql',
            ],
            \App\Enums\OperatingSystem::UBUNTU24 => [
                '12' => 'postgresql',
                '13' => 'postgresql',
                '14' => 'postgresql',
                '15' => 'postgresql',
                '16' => 'postgresql',
            ],
        ],
        'php' => [
            \App\Enums\OperatingSystem::UBUNTU20 => [
                '5.6' => 'php5.6-fpm',
                '7.0' => 'php7.0-fpm',
                '7.1' => 'php7.1-fpm',
                '7.2' => 'php7.2-fpm',
                '7.3' => 'php7.3-fpm',
                '7.4' => 'php7.4-fpm',
                '8.0' => 'php8.0-fpm',
                '8.1' => 'php8.1-fpm',
                '8.3' => 'php8.3-fpm',
                '8.4' => 'php8.4-fpm',
            ],
            \App\Enums\OperatingSystem::UBUNTU22 => [
                '5.6' => 'php5.6-fpm',
                '7.0' => 'php7.0-fpm',
                '7.1' => 'php7.1-fpm',
                '7.2' => 'php7.2-fpm',
                '7.3' => 'php7.3-fpm',
                '7.4' => 'php7.4-fpm',
                '8.0' => 'php8.0-fpm',
                '8.1' => 'php8.1-fpm',
                '8.2' => 'php8.2-fpm',
                '8.3' => 'php8.3-fpm',
                '8.4' => 'php8.4-fpm',
            ],
            \App\Enums\OperatingSystem::UBUNTU24 => [
                '5.6' => 'php5.6-fpm',
                '7.0' => 'php7.0-fpm',
                '7.1' => 'php7.1-fpm',
                '7.2' => 'php7.2-fpm',
                '7.3' => 'php7.3-fpm',
                '7.4' => 'php7.4-fpm',
                '8.0' => 'php8.0-fpm',
                '8.1' => 'php8.1-fpm',
                '8.2' => 'php8.2-fpm',
                '8.3' => 'php8.3-fpm',
                '8.4' => 'php8.4-fpm',
            ],
        ],
        'redis' => [
            \App\Enums\OperatingSystem::UBUNTU20 => [
                'latest' => 'redis',
            ],
            \App\Enums\OperatingSystem::UBUNTU22 => [
                'latest' => 'redis',
            ],
            \App\Enums\OperatingSystem::UBUNTU24 => [
                'latest' => 'redis',
            ],
        ],
        'supervisor' => [
            \App\Enums\OperatingSystem::UBUNTU20 => [
                'latest' => 'supervisor',
            ],
            \App\Enums\OperatingSystem::UBUNTU22 => [
                'latest' => 'supervisor',
            ],
            \App\Enums\OperatingSystem::UBUNTU24 => [
                'latest' => 'supervisor',
            ],
        ],
        'ufw' => [
            \App\Enums\OperatingSystem::UBUNTU20 => [
                'latest' => 'ufw',
            ],
            \App\Enums\OperatingSystem::UBUNTU22 => [
                'latest' => 'ufw',
            ],
            \App\Enums\OperatingSystem::UBUNTU24 => [
                'latest' => 'ufw',
            ],
        ],
        'vito-agent' => [
            \App\Enums\OperatingSystem::UBUNTU20 => [
                'latest' => 'vito-agent',
            ],
            \App\Enums\OperatingSystem::UBUNTU22 => [
                'latest' => 'vito-agent',
            ],
            \App\Enums\OperatingSystem::UBUNTU24 => [
                'latest' => 'vito-agent',
            ],
        ],
    ],

    /*
     * Site
     */
    'site_types' => [
        \App\Enums\SiteType::PHP,
        \App\Enums\SiteType::PHP_BLANK,
        \App\Enums\SiteType::LARAVEL,
        \App\Enums\SiteType::WORDPRESS,
        \App\Enums\SiteType::PHPMYADMIN,
    ],
    'site_types_class' => [
        \App\Enums\SiteType::PHP => \App\SiteTypes\PHPSite::class,
        \App\Enums\SiteType::PHP_BLANK => \App\SiteTypes\PHPBlank::class,
        \App\Enums\SiteType::LARAVEL => \App\SiteTypes\Laravel::class,
        \App\Enums\SiteType::WORDPRESS => \App\SiteTypes\Wordpress::class,
        \App\Enums\SiteType::PHPMYADMIN => \App\SiteTypes\PHPMyAdmin::class,
    ],

    /*
     * Source Control
     */
    'source_control_providers' => [
        'github',
        'gitlab',
        'bitbucket',
    ],
    'source_control_providers_class' => [
        'github' => \App\SourceControlProviders\Github::class,
        'gitlab' => \App\SourceControlProviders\Gitlab::class,
        'bitbucket' => \App\SourceControlProviders\Bitbucket::class,
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
     * firewall
     */
    'firewall_protocols_port' => [
        'tcp' => '',
        'udp' => '',
    ],

    /*
     * Disable these IPs for servers
     */
    'restricted_ip_addresses' => array_merge(
        ['127.0.0.1', 'localhost', '0.0.0.0'],
        explode(',', env('RESTRICTED_IP_ADDRESSES', ''))
    ),

    /*
     * Notification channels
     */
    'notification_channels_providers' => [
        \App\Enums\NotificationChannel::SLACK,
        \App\Enums\NotificationChannel::DISCORD,
        \App\Enums\NotificationChannel::EMAIL,
        \App\Enums\NotificationChannel::TELEGRAM,
    ],
    'notification_channels_providers_class' => [
        \App\Enums\NotificationChannel::SLACK => \App\NotificationChannels\Slack::class,
        \App\Enums\NotificationChannel::DISCORD => \App\NotificationChannels\Discord::class,
        \App\Enums\NotificationChannel::EMAIL => \App\NotificationChannels\Email::class,
        \App\Enums\NotificationChannel::TELEGRAM => \App\NotificationChannels\Telegram::class,
    ],

    /*
     * storage providers
     */
    'storage_providers' => [
        \App\Enums\StorageProvider::DROPBOX,
        \App\Enums\StorageProvider::FTP,
        \App\Enums\StorageProvider::LOCAL,
        \App\Enums\StorageProvider::S3,
    ],
    'storage_providers_class' => [
        \App\Enums\StorageProvider::DROPBOX => \App\StorageProviders\Dropbox::class,
        \App\Enums\StorageProvider::FTP => \App\StorageProviders\FTP::class,
        \App\Enums\StorageProvider::LOCAL => \App\StorageProviders\Local::class,
        \App\Enums\StorageProvider::S3 => \App\StorageProviders\S3::class,
    ],

    'ssl_types' => [
        \App\Enums\SslType::LETSENCRYPT,
        \App\Enums\SslType::CUSTOM,
    ],

    'metrics_data_retention' => [
        7,
        14,
        30,
        90,
    ],

    'tag_colors' => [
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
    'taggable_types' => [
        \App\Models\Server::class,
        \App\Models\Site::class,
    ],

    'user_roles' => [
        \App\Enums\UserRole::USER,
        \App\Enums\UserRole::ADMIN,
    ],

    'cronjob_intervals' => [
        '* * * * *' => 'Every Minute',
        '0 * * * *' => 'Hourly',
        '0 0 * * *' => 'Daily',
        '0 0 * * 0' => 'Weekly',
        '0 0 1 * *' => 'Monthly',
        'custom' => 'Custom',
    ],
];
