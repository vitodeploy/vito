<?php

namespace App\Actions\Application;

use App\Actions\Environment\DeleteEnvironment;
use App\DTOs\SocketEventDTO;
use App\Enums\ApplicationStatus;
use App\Enums\EnvironmentStatus;
use App\Events\SocketEvent;
use App\Jobs\Application\DeleteApplicationJob;
use App\Models\Application;
use App\Models\Component;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class DeleteApplication
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function delete(Application $application, array $input): void
    {
        Validator::make($input, ['name' => ['required', 'string', Rule::in([$application->name])]])->validate();

        DB::transaction(function () use ($application): void {
            $application = Application::query()->lockForUpdate()->findOrFail($application->id);
            if ($application->status === ApplicationStatus::DELETING) {
                throw ValidationException::withMessages(['name' => 'Application teardown is already in progress.']);
            }

            foreach ($application->environments()->orderBy('id')->lockForUpdate()->get() as $environment) {
                app(\App\Actions\EnvironmentDeployment\AssertEnvironmentIdle::class)->validate($environment, 'name');
            }

            if (! Component::query()->whereHas('environment', fn ($query) => $query->where('application_id', $application->id))->exists()
                && ! \App\Models\Ssl::query()->whereHas('environment', fn ($query) => $query->where('application_id', $application->id))->exists()) {
                foreach ($application->environments()->get() as $environment) {
                    app(\App\Actions\GitHook\SyncRepositoryGitHook::class)->disable($environment);
                }
                $application->delete();
                $this->broadcast($application, true);

                return;
            }

            $application->status = ApplicationStatus::DELETING;
            $application->save();
            $jobs = [];
            foreach ($application->environments()->orderBy('id')->get() as $environment) {
                $jobs = array_merge($jobs, app(DeleteEnvironment::class)->jobs($environment));
            }
            $jobs[] = new DeleteApplicationJob($application->id);
            $this->broadcast($application);
            $id = $application->id;
            DB::afterCommit(static function () use ($jobs, $id): void {
                Bus::chain($jobs)->onQueue('ssh')->catch(static function (Throwable $e) use ($id): void {
                    app(self::class)->failed($id);
                })->dispatch();
            });
            foreach ($application->environments()->get() as $environment) {
                app(\App\Actions\GitHook\SyncRepositoryGitHook::class)->disable($environment);
            }
        });
    }

    public function handle(int $applicationId): void
    {
        $application = Application::query()->find($applicationId);
        if (! $application || $application->status !== ApplicationStatus::DELETING) {
            return;
        }
        if ($application->environments()->exists()) {
            throw new RuntimeException('Environment teardown is incomplete.');
        }
        $application->delete();
        $this->broadcast($application, true);
    }

    public function failed(int $applicationId): void
    {
        $application = Application::query()->find($applicationId);
        if (! $application) {
            return;
        }
        $application->status = ApplicationStatus::DELETION_FAILED;
        $application->save();
        $application->environments()->where('status', EnvironmentStatus::DELETING)->update([
            'status' => EnvironmentStatus::DELETION_FAILED,
            'last_error' => 'Application teardown failed. Retry deletion from the application.',
        ]);
        $this->broadcast($application);
    }

    private function broadcast(Application $application, bool $deleted = false): void
    {
        $event = new SocketEventDTO(
            projectId: $application->project_id,
            type: $deleted ? 'application.deleted' : 'application.updated',
            data: ['id' => $application->id, 'status' => $application->status->getText()],
        );
        DB::afterCommit(static fn () => SocketEvent::dispatch($event));
    }
}
