<?php

namespace App\Actions\EnvironmentAttachment;

use App\Actions\EnvironmentDeployment\AssertEnvironmentIdle;
use App\Actions\Network\ResolveServerAddress;
use App\Enums\ApplicationStatus;
use App\Enums\ComponentStatus;
use App\Enums\ComponentType;
use App\Enums\EnvironmentAttachmentStatus;
use App\Enums\EnvironmentStatus;
use App\Enums\ServiceStatus;
use App\Models\DatabaseUser;
use App\Models\Environment;
use App\Models\EnvironmentAttachment;
use App\Models\Server;
use App\Models\Service;
use App\Services\SupportsNetworking;
use Illuminate\Validation\ValidationException;

class ValidateEnvironmentResource
{
    public function environment(Environment $environment): void
    {
        if ($environment->status !== EnvironmentStatus::READY || $environment->application->status !== ApplicationStatus::READY) {
            throw ValidationException::withMessages(['resource' => 'Resources cannot change during teardown.']);
        }
        app(AssertEnvironmentIdle::class)->validate($environment, 'resource');
    }

    public function service(Environment $environment, Service $service): void
    {
        if ($service->server->project_id !== $environment->application->project_id || ! $service->server->isReady()
            || $service->status !== ServiceStatus::READY || ! $service->handler() instanceof SupportsNetworking
            || ! in_array($service->name, ['mysql', 'mariadb', 'postgresql', 'redis', 'valkey'], true)) {
            throw ValidationException::withMessages(['resource' => 'Select a ready database, Redis or Valkey service in this project.']);
        }
        if ($service->server->firewall()?->status !== ServiceStatus::READY) {
            throw ValidationException::withMessages(['resource' => 'The resource server needs a ready firewall before attaching.']);
        }
        foreach ($environment->components()->where('type', ComponentType::APP)
            ->whereNotIn('status', [ComponentStatus::DELETING, ComponentStatus::DELETION_FAILED])->with('server')->get() as $component) {
            $this->connection($component->server, $service->server);
        }
    }

    /** @return array{network_id: ?int, ip: string, client_ip: string} */
    public function connection(Server $client, Server $target, string $field = 'resource'): array
    {
        $connection = app(ResolveServerAddress::class)->connection($client, $target);
        if ($connection === null) {
            throw ValidationException::withMessages([$field => 'Every app component needs a shared available private network with the resource server.']);
        }

        return $connection;
    }

    public function component(Environment $environment, Server $server): void
    {
        foreach ($environment->attachments()->with('server')->get() as $attachment) {
            if (! $attachment->isDeleting()) {
                $this->connection($server, $attachment->server, 'server_id');
            }
        }
    }

    public function ready(Environment $environment): bool
    {
        $components = $environment->components()->where('type', ComponentType::APP)->where('status', ComponentStatus::READY)->with('server')->get();
        foreach ($environment->attachments()->with('server', 'attachable')->get() as $attachment) {
            if ($attachment->status !== EnvironmentAttachmentStatus::READY || $attachment->service()?->status !== ServiceStatus::READY) {
                return false;
            }
            foreach ($components as $component) {
                $connection = app(ResolveServerAddress::class)->connection($component->server, $attachment->server);
                $access = $attachment->access[$component->id] ?? null;
                if ($connection === null || ! ($access['ready'] ?? false) || $access['host'] !== $connection['client_ip'] || $access['target_host'] !== $connection['ip']) {
                    return false;
                }
            }
        }

        return true;
    }

    public function databaseUser(DatabaseUser $user, ?EnvironmentAttachment $attachment = null): void
    {
        if ($user->environment_attachment_id !== null && $user->environment_attachment_id !== $attachment?->id) {
            throw ValidationException::withMessages(['database_user' => 'This user is managed by an environment resource. Detach the resource to remove its access.']);
        }
    }

    public function unattached(Service $service): void
    {
        if (EnvironmentAttachment::forService($service)->exists()) {
            throw ValidationException::withMessages(['service' => 'Detach environment resources and finish cleanup before changing this service configuration, networking or password.']);
        }
    }
}
