<?php

use App\Enums\OperatingSystem;
use App\Enums\StorageProvider;
use App\NotificationChannels\Discord;
use App\NotificationChannels\Email;
use App\NotificationChannels\Slack;
use App\NotificationChannels\Telegram;
use App\ServerProviders\AWS;
use App\ServerProviders\DigitalOcean;
use App\ServerProviders\Hetzner;
use App\ServerProviders\Linode;
use App\ServerProviders\Vultr;
use App\SiteTypes\Laravel;
use App\SiteTypes\PHPBlank;
use App\SiteTypes\PHPMyAdmin;
use App\SiteTypes\PHPSite;
use App\SiteTypes\Wordpress;
use App\SourceControlProviders\Bitbucket;
use App\SourceControlProviders\Github;
use App\SourceControlProviders\Gitlab;
use App\SSH\Services\Database\Mariadb;
use App\SSH\Services\Database\Mysql;
use App\SSH\Services\Database\Postgresql;
use App\SSH\Services\Firewall\Ufw;
use App\SSH\Services\PHP\PHP;
use App\SSH\Services\ProcessManager\Supervisor;
use App\SSH\Services\Redis\Redis;
use App\SSH\Services\Webserver\Nginx;
use App\StorageProviders\Dropbox;
use App\StorageProviders\FTP;

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
    ],
    'webservers' => ['none', 'nginx'],
    'php_versions' => [
        'none',
        // '5.6',
        '7.0',
        '7.1',
        '7.2',
        '7.3',
        '7.4',
        '8.0',
        '8.1',
        '8.2',
        '8.3',
    ],
    'databases' => [
        'none',
        'mysql57',
        'mysql80',
        'mariadb103',
        'mariadb104',
        'postgresql12',
        'postgresql13',
        'postgresql14',
        'postgresql15',
        'postgresql16',
    ],
    'databases_name' => [
        'none' => 'none',
        'mysql57' => 'mysql',
        'mysql80' => 'mysql',
        'mariadb103' => 'mariadb',
        'mariadb104' => 'mariadb',
        'postgresql12' => 'postgresql',
        'postgresql13' => 'postgresql',
        'postgresql14' => 'postgresql',
        'postgresql15' => 'postgresql',
        'postgresql16' => 'postgresql',
    ],
    'databases_version' => [
        'none' => '',
        'mysql57' => '5.7',
        'mysql80' => '8.0',
        'mariadb' => '10.3',
        'mariadb103' => '10.3',
        'mariadb104' => '10.4',
        'postgresql12' => '12',
        'postgresql13' => '13',
        'postgresql14' => '14',
        'postgresql15' => '15',
        'postgresql16' => '16',
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
        \App\Enums\ServerProvider::AWS => AWS::class,
        \App\Enums\ServerProvider::LINODE => Linode::class,
        \App\Enums\ServerProvider::DIGITALOCEAN => DigitalOcean::class,
        \App\Enums\ServerProvider::VULTR => Vultr::class,
        \App\Enums\ServerProvider::HETZNER => Hetzner::class,
    ],
    'server_providers_default_user' => [
        'custom' => [
            'ubuntu_18' => 'root',
            'ubuntu_20' => 'root',
            'ubuntu_22' => 'root',
        ],
        'aws' => [
            'ubuntu_18' => 'ubuntu',
            'ubuntu_20' => 'ubuntu',
            'ubuntu_22' => 'ubuntu',
        ],
        'linode' => [
            'ubuntu_18' => 'root',
            'ubuntu_20' => 'root',
            'ubuntu_22' => 'root',
        ],
        'digitalocean' => [
            'ubuntu_18' => 'root',
            'ubuntu_20' => 'root',
            'ubuntu_22' => 'root',
        ],
        'vultr' => [
            'ubuntu_18' => 'root',
            'ubuntu_20' => 'root',
            'ubuntu_22' => 'root',
        ],
        'hetzner' => [
            'ubuntu_18' => 'root',
            'ubuntu_20' => 'root',
            'ubuntu_22' => 'root',
        ],
    ],

    /*
     * Service
     */
    'service_handlers' => [
        'nginx' => Nginx::class,
        'mysql' => Mysql::class,
        'mariadb' => Mariadb::class,
        'postgresql' => Postgresql::class,
        'redis' => Redis::class,
        'php' => PHP::class,
        'ufw' => Ufw::class,
        'supervisor' => Supervisor::class,
    ],
    'add_on_services' => [
        // add-on services
    ],
    'service_units' => [
        'nginx' => [
            'ubuntu_18' => [
                'latest' => 'nginx',
            ],
            'ubuntu_20' => [
                'latest' => 'nginx',
            ],
            'ubuntu_22' => [
                'latest' => 'nginx',
            ],
        ],
        'mysql' => [
            'ubuntu_18' => [
                '5.7' => 'mysql',
                '8.0' => 'mysql',
            ],
            'ubuntu_20' => [
                '5.7' => 'mysql',
                '8.0' => 'mysql',
            ],
            'ubuntu_22' => [
                '5.7' => 'mysql',
                '8.0' => 'mysql',
            ],
        ],
        'mariadb' => [
            'ubuntu_18' => [
                '10.3' => 'mariadb',
                '10.4' => 'mariadb',
            ],
            'ubuntu_20' => [
                '10.3' => 'mariadb',
                '10.4' => 'mariadb',
            ],
            'ubuntu_22' => [
                '10.3' => 'mariadb',
                '10.4' => 'mariadb',
            ],
        ],
        'postgresql' => [
            'ubuntu_18' => [
                '12' => 'postgresql',
                '13' => 'postgresql',
                '14' => 'postgresql',
                '15' => 'postgresql',
                '16' => 'postgresql',
            ],
            'ubuntu_20' => [
                '12' => 'postgresql',
                '13' => 'postgresql',
                '14' => 'postgresql',
                '15' => 'postgresql',
                '16' => 'postgresql',
            ],
            'ubuntu_22' => [
                '12' => 'postgresql',
                '13' => 'postgresql',
                '14' => 'postgresql',
                '15' => 'postgresql',
                '16' => 'postgresql',
            ],
        ],
        'php' => [
            'ubuntu_18' => [
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
            ],
            'ubuntu_20' => [
                '5.6' => 'php5.6-fpm',
                '7.0' => 'php7.0-fpm',
                '7.1' => 'php7.1-fpm',
                '7.2' => 'php7.2-fpm',
                '7.3' => 'php7.3-fpm',
                '7.4' => 'php7.4-fpm',
                '8.0' => 'php8.0-fpm',
                '8.1' => 'php8.1-fpm',
                '8.3' => 'php8.3-fpm',
            ],
            'ubuntu_22' => [
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
            ],
        ],
        'redis' => [
            'ubuntu_18' => [
                'latest' => 'redis',
            ],
            'ubuntu_20' => [
                'latest' => 'redis',
            ],
            'ubuntu_22' => [
                'latest' => 'redis',
            ],
        ],
        'supervisor' => [
            'ubuntu_18' => [
                'latest' => 'supervisor',
            ],
            'ubuntu_20' => [
                'latest' => 'supervisor',
            ],
            'ubuntu_22' => [
                'latest' => 'supervisor',
            ],
        ],
        'ufw' => [
            'ubuntu_18' => [
                'latest' => 'ufw',
            ],
            'ubuntu_20' => [
                'latest' => 'ufw',
            ],
            'ubuntu_22' => [
                'latest' => 'ufw',
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
        \App\Enums\SiteType::PHP => PHPSite::class,
        \App\Enums\SiteType::PHP_BLANK => PHPBlank::class,
        \App\Enums\SiteType::LARAVEL => Laravel::class,
        \App\Enums\SiteType::WORDPRESS => Wordpress::class,
        \App\Enums\SiteType::PHPMYADMIN => PHPMyAdmin::class,
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
        'github' => Github::class,
        'gitlab' => Gitlab::class,
        'bitbucket' => Bitbucket::class,
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
        \App\Enums\NotificationChannel::SLACK => Slack::class,
        \App\Enums\NotificationChannel::DISCORD => Discord::class,
        \App\Enums\NotificationChannel::EMAIL => Email::class,
        \App\Enums\NotificationChannel::TELEGRAM => Telegram::class,
    ],

    /*
     * storage providers
     */
    'storage_providers' => [
        StorageProvider::DROPBOX,
        StorageProvider::FTP,
    ],
    'storage_providers_class' => [
        'dropbox' => Dropbox::class,
        'ftp' => FTP::class,
    ],

    'ssl_types' => [
        \App\Enums\SslType::LETSENCRYPT,
        \App\Enums\SslType::CUSTOM,
    ],
];
