<?php

use App\Providers\AppServiceProvider;
use App\Providers\ComponentServiceProvider;
use App\Providers\DemoServiceProvider;
use App\Providers\DNSProviderServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\NotificationChannelServiceProvider;
use App\Providers\PluginsServiceProvider;
use App\Providers\RouteServiceProvider;
use App\Providers\ServerProviderServiceProvider;
use App\Providers\ServiceTypeServiceProvider;
use App\Providers\SiteTypeServiceProvider;
use App\Providers\SourceControlServiceProvider;
use App\Providers\StorageProviderServiceProvider;
use App\Providers\ToolingServiceProvider;
use App\Providers\WorkflowServiceProvider;
use Illuminate\Auth\AuthServiceProvider;
use Illuminate\Auth\Passwords\PasswordResetServiceProvider;
use Illuminate\Broadcasting\BroadcastServiceProvider;
use Illuminate\Bus\BusServiceProvider;
use Illuminate\Cache\CacheServiceProvider;
use Illuminate\Cookie\CookieServiceProvider;
use Illuminate\Database\DatabaseServiceProvider;
use Illuminate\Encryption\EncryptionServiceProvider;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Foundation\Providers\ConsoleSupportServiceProvider;
use Illuminate\Foundation\Providers\FoundationServiceProvider;
use Illuminate\Hashing\HashServiceProvider;
use Illuminate\Mail\MailServiceProvider;
use Illuminate\Notifications\NotificationServiceProvider;
use Illuminate\Pagination\PaginationServiceProvider;
use Illuminate\Pipeline\PipelineServiceProvider;
use Illuminate\Queue\QueueServiceProvider;
use Illuminate\Redis\RedisServiceProvider;
use Illuminate\Session\SessionServiceProvider;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\TranslationServiceProvider;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\View\ViewServiceProvider;

return [

    'name' => env('APP_NAME', 'Vito'),

    'env' => env('APP_ENV', 'production'),

    'debug' => (bool) env('APP_DEBUG', false),

    'url' => env('APP_URL', 'http://localhost'),
    'ws_url' => env('WS_URL'),

    'asset_url' => env('ASSET_URL'),

    'timezone' => 'UTC',

    'locale' => 'en',

    'fallback_locale' => 'en',

    'faker_locale' => 'en_US',

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    'maintenance' => [
        'driver' => 'file',
    ],

    'providers' => [

        AuthServiceProvider::class,
        BroadcastServiceProvider::class,
        BusServiceProvider::class,
        CacheServiceProvider::class,
        ConsoleSupportServiceProvider::class,
        CookieServiceProvider::class,
        DatabaseServiceProvider::class,
        EncryptionServiceProvider::class,
        FilesystemServiceProvider::class,
        FoundationServiceProvider::class,
        HashServiceProvider::class,
        MailServiceProvider::class,
        NotificationServiceProvider::class,
        PaginationServiceProvider::class,
        PipelineServiceProvider::class,
        QueueServiceProvider::class,
        RedisServiceProvider::class,
        PasswordResetServiceProvider::class,
        SessionServiceProvider::class,
        TranslationServiceProvider::class,
        ValidationServiceProvider::class,
        ViewServiceProvider::class,

        AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        RouteServiceProvider::class,
        DemoServiceProvider::class,
        PluginsServiceProvider::class,
        SiteTypeServiceProvider::class,
        ToolingServiceProvider::class,
        ServerProviderServiceProvider::class,
        StorageProviderServiceProvider::class,
        SourceControlServiceProvider::class,
        DNSProviderServiceProvider::class,
        NotificationChannelServiceProvider::class,
        ServiceTypeServiceProvider::class,
        ComponentServiceProvider::class,
        HorizonServiceProvider::class,
        WorkflowServiceProvider::class,
    ],

    'aliases' => Facade::defaultAliases()->merge([
    ])->toArray(),

    'version' => '4.3.0-beta-2',

    'demo' => env('APP_DEMO', false),

    'force_https' => env('FORCE_HTTPS', false),

    'trusted_proxies' => env('TRUSTED_PROXIES', '127.0.0.0/8,10.0.0.0/8,172.16.0.0/12,192.168.0.0/16,::1,fc00::/7'),

    'self_hosted' => env('SELF_HOSTED', true),
];
