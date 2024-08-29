<?php

namespace App\Facades;

use App\Support\Testing\FTPFake;
use FTP\Connection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool|Connection connect(string $host, string $port, bool $ssl = false)
 * @method static bool login(string $username, string $password, bool|Connection $connection)
 * @method static void close(bool|Connection $connection)
 * @method static bool passive(bool|Connection $connection, bool $passive)
 * @method static bool delete(bool|Connection $connection, string $path)
 * @method static void assertConnected(string $host)
 */
class FTP extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'ftp';
    }

    public static function fake(): FTPFake
    {
        static::swap($fake = new FTPFake());

        return $fake;
    }
}
