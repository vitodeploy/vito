<?php

namespace App\Facades;

use App\Support\Testing\SSHFake;
use Illuminate\Support\Facades\Facade as FacadeAlias;

/**
 * Class SSH
 *
 * @method static \App\Helpers\SSH|SSHFake init(\App\Models\Server $server, string $asUser = null)
 * @method static setLog(?\App\Models\ServerLog $log)
 * @method static connect()
 * @method static string exec(string $command, string $log = '', int $siteId = null, ?bool $stream = false, callable $streamCallback = null)
 * @method static string upload(string $local, string $remote, ?string $owner = null)
 * @method static string download(string $local, string $remote)
 * @method static string write(string $path, string $content, string $owner = null)
 * @method static string assertExecuted(array<int, string>|string $commands)
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
