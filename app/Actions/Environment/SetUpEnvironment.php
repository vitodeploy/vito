<?php

namespace App\Actions\Environment;

use App\Actions\Component\CreateComponent;
use App\Actions\EnvironmentAttachment\AttachDatabase;
use App\Actions\GitHook\SyncRepositoryGitHook;
use App\ComponentCapabilities\Web;
use App\Enums\ComponentType;
use App\Models\Environment;
use App\Models\Server;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class SetUpEnvironment
{
    /**
     * Places a new environment on its first server and queues its first deploy for when setup finishes.
     *
     * @param  array<string, mixed>  $input
     */
    public function setUp(Environment $environment, array $input): void
    {
        $application = $environment->application;
        $server = Server::query()->where('project_id', $application->project_id)->find($input['server_id']);

        app(CreateComponent::class)->create($environment, [
            'name' => 'web',
            'type' => ComponentType::APP->value,
            'server_id' => $input['server_id'],
            'user' => ($input['user'] ?? null) ?: $server?->getSshUser(),
            'capabilities' => $input['capabilities'] ?? [['capability' => Web::id(), 'config' => []]],
        ]);

        if (($input['database'] ?? false) && $server) {
            $this->attachDatabase($environment, $server);
        }

        $environment->refresh();
        $environment->deploy_on_ready = true;
        $environment->auto_deploy = (bool) ($input['auto_deploy'] ?? true);
        $environment->save();
        if ($environment->auto_deploy) {
            DB::afterCommit(fn () => $this->enableAutoDeploy($environment));
        }
    }

    private function attachDatabase(Environment $environment, Server $server): void
    {
        $charsets = $server->database()?->type_data['charsets'] ?? [];
        $charset = isset($charsets['utf8mb4']) ? 'utf8mb4' : ($server->database()?->type_data['defaultCharset'] ?? '');
        $collations = $charsets[$charset] ?? [];
        $base = Str::limit(Str::slug($environment->application->name.' '.$environment->slug, '_'), 56, '') ?: 'app';
        $name = $base;
        for ($suffix = 2; $server->databases()->where('name', $name)->exists(); $suffix++) {
            $name = $base.'_'.$suffix;
        }

        try {
            app(AttachDatabase::class)->attach($environment, [
                'server_id' => $server->id,
                'name' => $name,
                'charset' => $charset,
                'collation' => $collations['default'] ?? ($collations['list'][0] ?? ''),
            ]);
        } catch (ValidationException $e) {
            throw ValidationException::withMessages(['database' => $e->validator->errors()->all()]);
        }
    }

    private function enableAutoDeploy(Environment $environment): void
    {
        $application = $environment->application;
        try {
            if ($application->sourceControl) {
                app(SyncRepositoryGitHook::class)->sync($application->sourceControl, $application->repository);
            }
        } catch (Throwable $e) {
            Log::warning('Automatic deployment could not be enabled', ['environment_id' => $environment->id, 'exception' => $e::class]);
            $environment->auto_deploy = false;
            $environment->auto_deploy_failed = true;
            $environment->save();
            try {
                app(SyncRepositoryGitHook::class)->sync($application->sourceControl, $application->repository);
            } catch (Throwable $cleanup) {
                Log::warning('Repository hook cleanup failed; scheduled reconciliation will retry.', ['environment_id' => $environment->id, 'exception' => $cleanup::class]);
            }
        }
    }
}
