<?php

namespace App\Tooling;

use App\DTOs\ToolingTargetDTO;
use App\Exceptions\SSHError;

class ComposerTooling extends AbstractTooling
{
    public const string INSTALL_DIR = '.local/vito/bin';

    public static function id(): string
    {
        return 'composer';
    }

    public static function label(): string
    {
        return 'Composer';
    }

    public static function description(): string
    {
        return 'Dependency manager for PHP, used to install and update project dependencies during deployment.';
    }

    public static function supportedVersions(): array
    {
        return ['2'];
    }

    public static function commands(): array
    {
        return ['composer'];
    }

    /**
     * @throws SSHError
     */
    public function install(ToolingTargetDTO $target, string $version): void
    {
        $target->server->ssh($target->user)->exec(
            view('ssh.composer.install', [
                'phpVersion' => $target->phpVersion,
                'majorVersion' => $version,
                'installDir' => self::INSTALL_DIR,
            ]),
            'install-composer',
            $target->siteId
        );
    }

    /**
     * @throws SSHError
     */
    public function uninstall(ToolingTargetDTO $target): void
    {
        $target->server->ssh($target->user)->exec(
            view('ssh.composer.uninstall', [
                'installDir' => self::INSTALL_DIR,
            ]),
            'uninstall-composer',
            $target->siteId
        );
    }

    public function pathContributions(ToolingTargetDTO $target): array
    {
        return ['/home/'.$target->user.'/'.self::INSTALL_DIR];
    }
}
