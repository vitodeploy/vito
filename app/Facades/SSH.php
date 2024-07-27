<?php

namespace App\Facades;

use App\Models\Server;
use App\Models\ServerLog;
use App\Support\Testing\SSHFake;
use Illuminate\Support\Facades\Facade as FacadeAlias;

/**
 * Class SSH
 *
 * @method static init(Server $server, string $asUser = null)
 * @method static setLog(?ServerLog $log)
 * @method static connect()
 * @method static string exec(string $command, string $log = '', int $siteId = null, ?bool $stream = false)
 * @method static string assertExecuted(array|string $commands)
 * @method static string assertExecutedContains(string $command)
 * @method static string assertFileUploaded(string $toPath, ?string $content = null)
 * @method static string getUploadedLocalPath()
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
