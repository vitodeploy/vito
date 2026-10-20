<?php

namespace App\Actions\Environment;

use App\Actions\EnvironmentAttachment\DetachEnvironmentResource;
use App\Actions\EnvironmentDeployment\AssertEnvironmentIdle;
use App\Actions\GitHook\SyncRepositoryGitHook;
use App\DTOs\SocketEventDTO;
use App\Enums\ApplicationStatus;
use App\Enums\ComponentStatus;
use App\Enums\EnvironmentStatus;
use App\Events\SocketEvent;
use App\Jobs\Component\DeleteComponentJob;
use App\Jobs\Environment\DeleteEnvironmentJob;
use App\Jobs\EnvironmentAttachment\SyncEnvironmentResourceAccessJob;
use App\Jobs\EnvironmentDomain\DeleteEnvironmentDomainsJob;
use App\Models\Application;
use App\Models\Environment;
use App\Models\Ssl;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class DeleteEnvironment
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function delete(Environment $environment, array $input): void
    {
        Validator::make($input, ['name' => ['required', 'string', Rule::in([$environment->name])]])->validate();

        DB::transaction(function () use ($environment): void {
            $application = Application::query()->lockForUpdate()->findOrFail($environment->application_id);
            $environment = Environment::query()->lockForUpdate()->findOrFail($environment->id);
            app(AssertEnvironmentIdle::class)->validate($environment, 'name');
            if ($environment->status === EnvironmentStatus::DELETING || $application->status !== ApplicationStatus::READY) {
                throw ValidationException::withMessages(['name' => 'Teardown is already in progress. Retry it from the application if necessary.']);
            }

            if (! $environment->attachments()->exists() && ! $environment->components()->exists() && ! Ssl::query()->where('environment_id', $environment->id)->exists()) {
                app(SyncRepositoryGitHook::class)->disable($environment);
                $environment->delete();
                $this->broadcast($environment, true);

                return;
            }

            $jobs = $this->jobs($environment);
            $id = $environment->id;
            DB::afterCommit(static function () use ($jobs, $id): void {
                Bus::chain($jobs)->onQueue('ssh')->catch(static function (Throwable $e) use ($id): void {
                    app(self::class)->failed($id);
                })->dispatch();
            });
            app(SyncRepositoryGitHook::class)->disable($environment);
        });
    }

    /**
     * @return list<DeleteEnvironmentDomainsJob|SyncEnvironmentResourceAccessJob|DeleteComponentJob|DeleteEnvironmentJob>
     */
    public function jobs(Environment $environment): array
    {
        app(AssertEnvironmentIdle::class)->validate($environment, 'name');
        $environment->status = EnvironmentStatus::DELETING;
        $environment->last_error = null;
        $environment->save();
        $jobs = [];
        $domainServers = $environment->components()->whereHas('domains')->distinct()->pluck('server_id')
            ->merge(Ssl::query()->where('environment_id', $environment->id)->whereNotNull('server_id')->pluck('server_id'))->unique();
        foreach ($domainServers as $serverId) {
            $jobs[] = new DeleteEnvironmentDomainsJob($environment->id, $serverId);
        }
        foreach ($environment->components()->orderBy('id')->get() as $component) {
            $component->status = ComponentStatus::DELETING;
            $component->last_error = null;
            $component->save();
            $jobs[] = new DeleteComponentJob($component->id, $component->server_id);
        }
        foreach ($environment->attachments()->orderBy('id')->get() as $attachment) {
            app(DetachEnvironmentResource::class)->mark($attachment);
            $jobs[] = new SyncEnvironmentResourceAccessJob($attachment->id, $attachment->server_id);
        }
        $this->broadcast($environment);
        $jobs[] = new DeleteEnvironmentJob($environment->id);

        return $jobs;
    }

    public function handle(int $environmentId): void
    {
        $environment = Environment::query()->find($environmentId);
        if (! $environment || $environment->status !== EnvironmentStatus::DELETING) {
            return;
        }
        if ($environment->attachments()->exists() || $environment->components()->exists() || Ssl::query()->where('environment_id', $environment->id)->exists()) {
            throw new RuntimeException('Component, resource or certificate teardown is incomplete.');
        }
        $environment->delete();
        $this->broadcast($environment, true);
    }

    public function failed(int $environmentId): void
    {
        $environment = Environment::query()->find($environmentId);
        if (! $environment) {
            return;
        }
        $environment->status = EnvironmentStatus::DELETION_FAILED;
        $environment->last_error = 'Environment teardown failed. Resolve the failed component and retry deletion.';
        $environment->save();
        $this->broadcast($environment);
    }

    private function broadcast(Environment $environment, bool $deleted = false): void
    {
        $event = new SocketEventDTO(
            projectId: $environment->application->project_id,
            type: $deleted ? 'environment.deleted' : 'environment.updated',
            data: [
                'id' => $environment->id, 'application_id' => $environment->application_id,
                'status' => $environment->status->getText(), 'application_status' => $environment->application->status->getText(),
            ],
        );
        DB::afterCommit(static fn () => SocketEvent::dispatch($event));
    }
}
