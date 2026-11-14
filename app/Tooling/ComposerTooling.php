<?php

namespace App\Tooling;

use App\DTOs\ToolingTargetDTO;
use App\Exceptions\SSHError;
use App\Models\Site;

class ComposerTooling extends AbstractTooling implements TargetToolingInterface
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
        $this->installForTarget(ToolingTargetDTO::forSite($site), $version);
    }

    /**
     * @throws SSHError
     */
    public function installForTarget(ToolingTargetDTO $target, string $version): void
    {
        $target->server->ssh($target->user)->exec(
            view('ssh.composer.install', [
                'phpVersion' => $target->phpVersion,
                'majorVersion' => $version,
                'installDir' => self::INSTALL_DIR,
                'configDir' => $target->configDir,
            ]),
            'install-composer',
            $target->siteId
        );
    }

    /**
     * @throws SSHError
     */
    public function uninstall(Site $site): void
    {
        $this->uninstallForTarget(ToolingTargetDTO::forSite($site));
    }

    /**
     * @throws SSHError
     */
    public function uninstallForTarget(ToolingTargetDTO $target): void
    {
        $target->server->ssh($target->user)->exec(
            view('ssh.composer.uninstall', [
                'installDir' => self::INSTALL_DIR,
                'configDir' => $target->configDir,
            ]),
            'uninstall-composer',
            $target->siteId
        );
    }

    public function pathContributionsForTarget(ToolingTargetDTO $target): array
    {
        return [($target->configDir ?? '/home/'.$target->user).'/'.self::INSTALL_DIR];
    }
}
