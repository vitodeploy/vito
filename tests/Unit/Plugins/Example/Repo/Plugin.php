<?php

namespace App\Vito\Plugins\Example\Repo;

use App\Plugins\AbstractPlugin;

class Plugin extends AbstractPlugin
{
    protected string $name = 'Example';

    protected string $description = 'Example plugin';

    public static array $calledMethods = [];

    public function __construct()
    {
        self::$calledMethods = [];
    }

    public static function getMethods(): array
    {
        return self::$calledMethods;
    }

    private static function recordCall(string $method): void
    {
        if (! isset(self::$calledMethods[$method])) {
            self::$calledMethods[$method] = 0;
        }
        self::$calledMethods[$method]++;
    }

    public function boot(): void
    {
        self::recordCall('boot');
    }

    public function enable(): void
    {
        self::recordCall('enable');
    }

    public function disable(): void
    {
        self::recordCall('disable');
    }

    public function install(): void
    {
        self::recordCall('install');
    }

    public function uninstall(): void
    {
        self::recordCall('uninstall');
    }
}
