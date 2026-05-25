<?php

namespace App\SiteTypes;

use App\Actions\Worker\CreateWorker;
use App\DTOs\DynamicField;
use App\Exceptions\FailedToDeployGitKey;
use App\Exceptions\SSHError;
use App\Models\Deployment;
use App\Models\SourceControl;
use App\Models\Worker;

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
            'start_command' => ['nullable', 'string', 'max:255', 'not_regex:/[\r\n]/'],
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

    /**
     * @return array<int, string>
     */
    protected function deployCommands(): array
    {
        return [];
    }

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
        return implode("\n\n", array_merge(
            ['git pull origin $BRANCH'],
            $this->deployCommands(),
        ))."\n";
    }

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
