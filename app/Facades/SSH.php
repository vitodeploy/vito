<?php

namespace App\Facades;

use App\Models\Server;
use App\Support\Testing\SSHFake;
use Illuminate\Support\Facades\Facade as FacadeAlias;

/**
 * Class SSH
 *
 * @method static init(Server $server, string $asUser = null)
 * @method static setLog(string $logType, int $siteId = null)
 * @method static connect()
 * @method static string exec(string $command, string $log = '', int $siteId = null)
 * @method string exec(string $command, string $log = '', int $siteId = null)
 * @method static disconnect()
 */
class SSH extends FacadeAlias
{
    public static function fake(): SSHFake
    {
        static::swap($fake = new SSHFake());

        return $fake;
    }

    protected static function getFacadeAccessor(): string
    {
        return 'ssh';
    }
}
