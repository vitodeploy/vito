<?php

namespace App\Facades;

use App\Support\Testing\SFTPFake;
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool connect(string $host, int $port, string $username, string $password)
 * @method static void assertConnected(string $host)
 */
class SFTP extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'sftp';
    }

    public static function fake(): SFTPFake
    {
        static::swap($fake = new SFTPFake);

        return $fake;
    }
}
