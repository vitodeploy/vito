<?php

namespace App\Facades;

use App\Contracts\ServerConnection;
use App\Helpers\LocalSocket;
use App\Models\Server;
use App\Models\ServerLog;
use App\Support\Testing\SSHFake;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Facade as FacadeAlias;

/**
 * Class SSH
 *
 * @method static setLog(?ServerLog $log)
 * @method static \App\Helpers\SSH useLog(string $disk, string $path)
 * @method static connect()
 * @method static string exec(string|View $command, string $log = '', int $siteId = null, ?bool $stream = false, callable $streamCallback = null)
 * @method static string upload(string $local, string $remote, ?string $owner = null)
 * @method static string download(string $local, string $remote)
 * @method static string write(string $path, string $content, string $owner = null)
 * @method static string assertExecuted(mixed $commands)
 * @method static string assertExecutedContains(string $command)
 * @method static string assertNotExecutedContains(string $command, string $message = '')
 * @method static string assertFileUploaded(string $toPath, ?string $content = null)
 * @method static string getUploadedLocalPath()
 * @method static disconnect()
 */
class SSH extends FacadeAlias
{
    protected static ?SSHFake $fake = null;

    public static function fake(?string $output = null): SSHFake
    {
        static::$fake = new SSHFake($output);
        static::swap(static::$fake);

        return static::$fake;
    }

    public static function clearFake(): void
    {
        static::$fake = null;
        static::clearResolvedInstance(static::getFacadeAccessor());
    }

    /**
     * Initialize a connection to the server.
     * Routes to LocalSocket for local servers, SSH for remote servers.
     */
    public static function init(Server $server, ?string $asUser = null): ServerConnection|SSHFake
    {
        // If we're using a fake, return it
        if (static::$fake !== null) {
            return static::$fake->init($server, $asUser);
        }

        // Route to LocalSocket for local servers
        if ($server->is_local) {
            return app(LocalSocket::class)->init($server, $asUser);
        }

        // Use regular SSH for remote servers
        return app('ssh')->init($server, $asUser);
    }

    protected static function getFacadeAccessor(): string
    {
        return 'ssh';
    }
}
