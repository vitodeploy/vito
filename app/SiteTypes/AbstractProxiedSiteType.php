<?php

namespace App\SiteTypes;

use App\Actions\Worker\CreateWorker;
use App\Actions\Worker\ManageWorker;
use App\Exceptions\FailedToDeployGitKey;
use App\Exceptions\SSHError;
use App\Models\Worker;

/**
 * Base for site types that are proxied by the webserver to a local
 * long-running application process running under supervisor (e.g.
 * `MiseNodeJS`, `MiseBun`). The site is the proxy target; nginx routes
 * traffic to a port owned by the supervisor-managed worker.
 *
 * Concrete subclasses declare:
 *  - `createTimeTools()` — tools to install (Node, Bun, pnpm, yarn, …).
 *  - `installCommand()`  — shell command run after `git clone` to install
 *    application dependencies (e.g. `npm ci`, `bun install --frozen-lockfile`).
 *  - `buildCommand()`    — shell command run after install (e.g. `npm run build`).
 *  - `startCommand()`    — shell command the supervisor worker runs.
 */
abstract class AbstractProxiedSiteType extends AbstractSiteType
{
    public static function supportsTooling(): bool
    {
        return true;
    }

    public function requiredServices(): array
    {
        return ['webserver', 'process_manager'];
    }

    public function baseCommands(): array
    {
        return [];
    }

    public function vhostData(): array
    {
        return ['is_reverse_proxy' => true];
    }

    abstract protected function installCommand(): string;

    abstract protected function buildCommand(): string;

    abstract protected function startCommand(): string;

    /**
     * @throws FailedToDeployGitKey
     * @throws SSHError
     */
    public function install(): void
    {
        $this->progress(0, 'isolating-user');
        $this->isolate();
        $this->progress(15, 'installing-tooling');
        $this->setupRequestedTooling();
        $this->progress(25, 'creating-vhost');
        $this->site->webserver()->createVHost($this->site);
        $this->progress(35, 'deploying-ssh-key');
        $this->deployKey();
        $this->progress(45, 'cloning-repository');
        $this->cloneRepository();
        $this->progress(55, 'installing-dependencies');
        $this->runInstallCommand();
        $this->progress(70, 'building');
        $this->runBuildCommand();
        $this->progress(85, 'creating-worker');
        $this->createWorker();
        $this->progress(90, 'finishing');
    }

    /**
     * @throws SSHError
     */
    protected function runInstallCommand(): void
    {
        $this->site->ssh()->exec(
            'cd '.escapeshellarg($this->site->path).' && '.$this->installCommand(),
            'site-install-deps',
            $this->site->id,
        );
    }

    /**
     * @throws SSHError
     */
    protected function runBuildCommand(): void
    {
        $this->site->ssh()->exec(
            'cd '.escapeshellarg($this->site->path).' && '.$this->buildCommand(),
            'site-build',
            $this->site->id,
        );
    }

    protected function createWorker(): void
    {
        /** @var ?Worker $worker */
        $worker = $this->site->workers()->where('name', 'app')->first();

        if ($worker) {
            app(ManageWorker::class)->restart($worker);

            return;
        }

        app(CreateWorker::class)->create(
            $this->site->server,
            [
                'name' => 'app',
                'command' => $this->workerCommand(),
                'user' => $this->site->user ?? $this->site->server->getSshUser(),
                'auto_start' => true,
                'auto_restart' => true,
                'numprocs' => 1,
            ],
            $this->site,
        );
    }

    protected function workerCommand(): string
    {
        return $this->startCommand();
    }
}
