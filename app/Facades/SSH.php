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
 * @method static string exec(string $command, string $log = '', int $siteId = null, ?bool $stream = false)
 * @method static string assertExecuted(array|string $commands)
 * @method static string assertExecutedContains(string $command)
 * @method static disconnect()
 */
class SSH extends FacadeAlias
{
    public static function fake(?string $output = null): SSHFake
    {
        static::swap($fake = new SSHFake($output));

        return $fake;
    }

    protected static function getFacadeAccessor(): string
    {
        return 'ssh';
    }
}
