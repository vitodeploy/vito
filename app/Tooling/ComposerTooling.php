<?php

namespace App\Tooling;

use App\Exceptions\SSHError;
use App\Models\Site;

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
    public function install(Site $site, string $version): void
    {
        $site->server->ssh($site->user)->exec(
            view('ssh.composer.install', [
                'phpVersion' => $site->php_version,
                'majorVersion' => $version,
                'installDir' => self::INSTALL_DIR,
            ]),
            'install-composer',
            $site->id
        );
    }

    /**
     * @throws SSHError
     */
    public function uninstall(Site $site): void
    {
        $site->server->ssh($site->user)->exec(
            view('ssh.composer.uninstall', [
                'installDir' => self::INSTALL_DIR,
            ]),
            'uninstall-composer',
            $site->id
        );
    }

    public function pathContributions(Site $site): array
    {
        return ['/home/'.$site->user.'/'.self::INSTALL_DIR];
    }
}
