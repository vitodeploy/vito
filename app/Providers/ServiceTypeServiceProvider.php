<?php

namespace App\Providers;

use App\Plugins\RegisterServiceType;
use App\Services\Database\Mariadb;
use App\Services\Database\Mysql;
use App\Services\Database\Postgresql;
use App\Services\Firewall\Ufw;
use App\Services\Monitoring\RemoteMonitor\RemoteMonitor;
use App\Services\Monitoring\VitoAgent\VitoAgent;
use App\Services\NodeJS\NodeJS;
use App\Services\PHP\PHP;
use App\Services\ProcessManager\Supervisor;
use App\Services\Redis\Redis;
use App\Services\Webserver\Caddy;
use App\Services\Webserver\Nginx;
use Illuminate\Support\ServiceProvider;

class ServiceTypeServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->webservers();
        $this->databases();
        $this->memoryDatabases();
        $this->firewalls();
        $this->processManagers();
        $this->monitoring();
        $this->php();
        $this->node();
    }

    private function webservers(): void
    {
        RegisterServiceType::make(Nginx::id())
            ->type(Nginx::type())
            ->label('Nginx')
            ->handler(Nginx::class)
            ->configPaths([
                [
                    'name' => 'nginx.conf',
                    'path' => '/etc/nginx/nginx.conf',
                    'sudo' => true,
                ],
            ])
            ->register();

        RegisterServiceType::make(Caddy::id())
            ->type(Caddy::type())
            ->label('Caddy (beta)')
            ->handler(Caddy::class)
            ->register();
    }

    private function databases(): void
    {
        RegisterServiceType::make(Mysql::id())
            ->type(Mysql::type())
            ->label('MySQL')
            ->handler(Mysql::class)
            ->versions([
                '8.4',
                '8.0',
            ])
            ->configPaths([
                [
                    'name' => 'my.cnf',
                    'path' => '/etc/mysql/my.cnf',
                    'sudo' => true,
                ],
            ])
            ->register();
        RegisterServiceType::make(Postgresql::id())
            ->type(Postgresql::type())
            ->label('PostgreSQL')
            ->handler(Postgresql::class)
            ->versions([
                '18',
                '17',
                '16',
                '15',
                '14',
                '13',
                '12',
            ])
            ->configPaths([
                [
                    'name' => 'postgresql.conf',
                    'path' => '/etc/postgresql/{version}/main/postgresql.conf',
                    'sudo' => true,
                ],
            ])
            ->register();
        RegisterServiceType::make(Mariadb::id())
            ->type(Mariadb::type())
            ->label('MariaDB')
            ->handler(Mariadb::class)
            ->versions([
                '11.4',
                '10.11',
                '10.6',
                '10.4',
                '10.3',
            ])
            ->configPaths([
                [
                    'name' => 'my.cnf',
                    'path' => '/etc/mysql/my.cnf',
                    'sudo' => true,
                ],
            ])
            ->register();
    }

    private function memoryDatabases(): void
    {
        RegisterServiceType::make(Redis::id())
            ->type(Redis::type())
            ->label('Redis')
            ->handler(Redis::class)
            ->configPaths([
                [
                    'name' => 'redis.conf',
                    'path' => '/etc/redis/redis.conf',
                    'sudo' => true,
                ],
            ])
            ->register();
    }

    private function firewalls(): void
    {
        RegisterServiceType::make(Ufw::id())
            ->type(Ufw::type())
            ->label('UFW')
            ->handler(Ufw::class)
            ->register();
    }

    private function processManagers(): void
    {
        RegisterServiceType::make(Supervisor::id())
            ->type(Supervisor::type())
            ->label('Supervisor')
            ->handler(Supervisor::class)
            ->configPaths([
                [
                    'name' => 'supervisord.conf',
                    'path' => '/etc/supervisor/supervisord.conf',
                    'sudo' => true,
                ],
            ])
            ->register();
    }

    private function monitoring(): void
    {
        RegisterServiceType::make(VitoAgent::id())
            ->type(VitoAgent::type())
            ->label('VitoAgent')
            ->handler(VitoAgent::class)
            ->register();

        RegisterServiceType::make(RemoteMonitor::id())
            ->type(RemoteMonitor::type())
            ->label('RemoteMonitor')
            ->handler(RemoteMonitor::class)
            ->register();
    }

    private function php(): void
    {
        RegisterServiceType::make(PHP::id())
            ->type(PHP::type())
            ->label('PHP')
            ->handler(PHP::class)
            ->versions([
                '8.5',
                '8.4',
                '8.3',
                '8.2',
                '8.1',
                '8.0',
                '7.4',
                '7.3',
                '7.2',
                '7.1',
                '7.0',
                '5.6',
            ])
            ->data([
                'extensions' => [
                    'imagick',
                    'exif',
                    'gmagick',
                    'gmp',
                    'intl',
                    'sqlite3',
                    'opcache',
                ],
            ])
            ->register();
    }

    private function node(): void
    {
        RegisterServiceType::make(NodeJS::id())
            ->type(NodeJS::type())
            ->label('Node.js')
            ->handler(NodeJS::class)
            ->versions([
                '22',
                '20',
                '18',
                '16',
            ])
            ->register();
    }
}
