<?php

namespace App\SiteTypes;

use App\Actions\Worker\CreateWorker;
use App\DTOs\DynamicField;
use App\Exceptions\FailedToDeployGitKey;
use App\Exceptions\SSHError;
use App\Models\Deployment;
use App\Models\SourceControl;
use App\Models\Worker;

/**
 * Base for site types that are proxied by the webserver to a local
 * long-running application process running under supervisor (e.g.
 * `NodeSite`, `BunSite`). The site is the proxy target; nginx routes
 * traffic to a port owned by the supervisor-managed worker.
 *
 * Install does the infrastructure only (isolate, install tooling, vhost,
 * deploy key, clone). Build + install of app deps + worker creation are
 * deferred to the first successful deploy via `afterDeploy()` so the user
 * gets a chance to review/customise the generated deploy script first.
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

    public function createRules(array $input): array
    {
        return [
            'source_control' => SourceControl::siteValidationRules($this->site->server),
            'repository' => ['required'],
            'branch' => ['required'],
            'port' => ['required', 'integer', 'between:1024,65535'],
        ];
    }

    public function createFields(array $input): array
    {
        return [
            'source_control_id' => $input['source_control'] ?? '',
            'repository' => $input['repository'] ?? '',
            'branch' => $input['branch'] ?? '',
            'port' => $input['port'] ?? '',
        ];
    }

    /**
     * Shared `DynamicField`s for the create form: source control, port,
     * repository, branch. Site type registrations in `SiteTypeServiceProvider`
     * merge these onto their tool-specific picker(s) so every proxied site
     * type asks for the same four fields in the same order.
     *
     * @return array<int, DynamicField>
     */
    public static function sharedFormFields(): array
    {
        return [
            DynamicField::make('source_control')
                ->component()
                ->label('Source Control'),
            DynamicField::make('port')
                ->text()
                ->label('Port')
                ->placeholder('3000')
                ->description('On which port your app will be running. Must be a non-privileged port (1024-65535).'),
            DynamicField::make('repository')
                ->text()
                ->label('Repository')
                ->placeholder('organization/repository'),
            DynamicField::make('branch')
                ->text()
                ->label('Branch')
                ->default('main'),
        ];
    }

    abstract protected function installCommand(): string;

    abstract protected function buildCommand(): string;

    abstract protected function defaultStartCommand(): string;

    protected function startCommand(): string
    {
        $command = $this->site->type_data['start_command'] ?? null;

        return is_string($command) && $command !== '' ? $command : $this->defaultStartCommand();
    }

    /**
     * @throws FailedToDeployGitKey
     * @throws SSHError
     */
    public function install(): void
    {
        $this->progress(0, 'isolating-user');
        $this->isolate();
        $this->progress(20, 'installing-tooling');
        $this->setupRequestedTooling();
        $this->progress(40, 'creating-vhost');
        $this->site->webserver()->createVHost($this->site);
        $this->progress(55, 'deploying-ssh-key');
        $this->deployKey();
        $this->progress(75, 'cloning-repository');
        $this->cloneRepository();
        $this->progress(90, 'finishing');
    }

    public function defaultDeploymentScript(): string
    {
        return view('deployment-scripts.proxied-site', [
            'installCommand' => $this->installCommand(),
            'buildCommand' => $this->buildCommand(),
        ])->render();
    }

    /**
     * Lazy worker bootstrap. Idempotent: short-circuits when a bootstrap
     * worker already exists for this site.
     */
    public function afterDeploy(Deployment $deployment): void
    {
        if ($this->bootstrapWorker() !== null) {
            return;
        }

        $created = app(CreateWorker::class)->create(
            $this->site->server,
            [
                'name' => 'app',
                'command' => $this->startCommand(),
                'user' => $this->site->user ?? $this->site->server->getSshUser(),
                'auto_start' => true,
                'auto_restart' => true,
                'numprocs' => 1,
            ],
            $this->site,
        );

        $this->site->jsonUpdate('type_data', 'bootstrap_worker_id', $created->id);
    }

    /**
     * Resolves the worker that manages this site's app process, if any.
     * Order: (1) type_data.bootstrap_worker_id, (2) backfill by name='app'
     * scoped to workers whose command matches a known default — guards
     * against silently adopting a user-created worker that happens to be
     * called 'app'.
     */
    public function bootstrapWorker(): ?Worker
    {
        $storedId = $this->site->type_data['bootstrap_worker_id'] ?? null;
        if (is_int($storedId) || (is_string($storedId) && ctype_digit($storedId))) {
            $worker = $this->site->workers()->find((int) $storedId);
            if ($worker) {
                return $worker;
            }
        }

        $candidate = $this->site->workers()
            ->where('name', 'app')
            ->whereIn('command', $this->knownDefaultStartCommands())
            ->first();

        if ($candidate) {
            $this->site->jsonUpdate('type_data', 'bootstrap_worker_id', $candidate->id);

            return $candidate;
        }

        return null;
    }

    /**
     * Default start commands the current AbstractProxiedSiteType subclasses
     * could have written to a pre-refactor worker.
     *
     * @return array<int, string>
     */
    protected function knownDefaultStartCommands(): array
    {
        return [
            'npm start',
            'pnpm start',
            'yarn start',
            'bun run start',
        ];
    }
}
