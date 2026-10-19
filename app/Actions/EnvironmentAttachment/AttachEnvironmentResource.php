<?php

namespace App\Actions\EnvironmentAttachment;

use App\Actions\Environment\MarkEnvironmentForRedeploy;
use App\Enums\EnvironmentAttachmentStatus;
use App\Jobs\EnvironmentAttachment\SyncEnvironmentResourceAccessJob;
use App\Models\Application;
use App\Models\Database;
use App\Models\Environment;
use App\Models\EnvironmentAttachment;
use App\Models\Server;
use App\Models\Service;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttachEnvironmentResource
{
    /**
     * @param array<string, mixed> $input
     * @param Closure(): (Database|Service) $resolve
     */
    public function attach(Environment $environment, Service $service, array $input, Closure $resolve): EnvironmentAttachment
    {
        try {
            $attachment = Cache::lock('unique-queue:server-'.$service->server_id, 600)->block(10, fn (): EnvironmentAttachment => DB::transaction(function () use ($environment, $service, $input, $resolve): EnvironmentAttachment {
                Application::query()->lockForUpdate()->findOrFail($environment->application_id);
                $environment = Environment::query()->lockForUpdate()->findOrFail($environment->id);
                $serverIds = $environment->components()->pluck('server_id')->push($service->server_id)->unique()->sort()->values();
                Server::query()->whereKey($serverIds)->orderBy('id')->lockForUpdate()->get();
                $service->refresh();
                $validation = app(ValidateEnvironmentResource::class);
                $validation->environment($environment);
                $validation->service($environment, $service);
                Validator::make($input, ['alias' => ['nullable', 'string', 'max:64', 'regex:/\A[A-Z][A-Z0-9_]*\z/']])->validate();
                $alias = $input['alias'] ?? null;
                $type = $service->type === 'database' ? (new Database)->getMorphClass() : $service->getMorphClass();
                if ($environment->attachments()->where('attachable_type', $type)->where('alias', $alias)->exists()) {
                    throw ValidationException::withMessages(['alias' => 'This environment already has an attachment of this kind with that alias. Leave only one default per kind.']);
                }
                $resource = $resolve();
                if ($resource->server_id !== $service->server_id || $environment->attachments()->whereMorphedTo('attachable', $resource)->exists()) {
                    throw ValidationException::withMessages(['resource' => 'This resource is unavailable or already attached to the environment.']);
                }
                $attachment = new EnvironmentAttachment;
                $attachment->environment_id = $environment->id;
                $attachment->server_id = $service->server_id;
                $attachment->attachable()->associate($resource);
                $attachment->alias = $alias;
                $attachment->credentials = $resource instanceof Database
                    ? ['username' => 'vito_'.strtolower(Str::random(8)), 'password' => Str::random(32)]
                    : ['password' => $service->secret ?? ''];
                $attachment->status = EnvironmentAttachmentStatus::PENDING;
                $attachment->save();
                app(MarkEnvironmentForRedeploy::class)->mark($environment);

                return $attachment;
            }));
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages(['resource' => 'Another operation on the resource server is in progress. Please retry.']);
        }
        SyncEnvironmentResourceAccessJob::dispatch($attachment->id, $attachment->server_id)->afterCommit();

        return $attachment;
    }
}
