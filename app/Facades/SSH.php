<?php

namespace App\Facades;

use App\Models\Server;
use App\Support\Testing\SSHFake;
use Illuminate\Support\Facades\Facade as FacadeAlias;

/**
 * Class SSH
 *
 * @method static \App\Helpers\SSH init(Server $server, string $asUser = null, bool $defaultKeys = false)
 * @method static setLog(string $logType, int $siteId = null)
 * @method static connect()
 * @method static exec($commands, string $log = '', int $siteId = null)
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
