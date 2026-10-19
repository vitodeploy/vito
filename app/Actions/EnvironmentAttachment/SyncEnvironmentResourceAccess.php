<?php

namespace App\Actions\EnvironmentAttachment;

use App\Actions\Database\CreateDatabase;
use App\Actions\Database\CreateDatabaseUser;
use App\Actions\Database\DeleteDatabaseUser;
use App\Actions\Environment\MarkEnvironmentForRedeploy;
use App\Actions\FirewallRule\ManageRule;
use App\Actions\Network\ResolveServerAddress;
use App\Actions\Service\ToggleNetworking;
use App\DTOs\SocketEventDTO;
use App\Enums\ComponentStatus;
use App\Enums\ComponentType;
use App\Enums\DatabaseStatus;
use App\Enums\EnvironmentAttachmentStatus;
use App\Enums\FirewallRuleStatus;
use App\Enums\ServiceStatus;
use App\Events\SocketEvent;
use App\Helpers\AttachmentVariables;
use App\Http\Resources\FirewallRuleResource;
use App\Jobs\EnvironmentAttachment\SyncEnvironmentResourceAccessJob;
use App\Models\Application;
use App\Models\Component;
use App\Models\Database;
use App\Models\Environment;
use App\Models\EnvironmentAttachment;
use App\Models\FirewallRule;
use App\Models\NetworkServer;
use App\Models\Server;
use App\Models\Service;
use App\Services\Database\Mysql;
use App\Services\Database\Postgresql;
use App\Services\Firewall\Firewall;
use App\Services\SupportsNetworking;
use App\Support\Cidr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class SyncEnvironmentResourceAccess
{
    public function dispatch(Environment $environment): void
    {
        foreach ($environment->attachments()->get() as $attachment) {
            if (! $attachment->isDeleting()) {
                EnvironmentAttachment::query()->whereKey($attachment->id)->whereNotIn('status', [EnvironmentAttachmentStatus::DELETING, EnvironmentAttachmentStatus::DELETION_FAILED])
                    ->update(['status' => EnvironmentAttachmentStatus::PENDING]);
            }
            SyncEnvironmentResourceAccessJob::dispatch($attachment->id, $attachment->server_id)->afterCommit();
        }
    }

    public function retry(EnvironmentAttachment $attachment): void
    {
        DB::transaction(function () use ($attachment): void {
            Application::query()->lockForUpdate()->findOrFail($attachment->environment->application_id);
            $environment = Environment::query()->lockForUpdate()->findOrFail($attachment->environment_id);
            app(ValidateEnvironmentResource::class)->environment($environment);
            $attachment->refresh();
            if (! in_array($attachment->status, [EnvironmentAttachmentStatus::FAILED, EnvironmentAttachmentStatus::DELETION_FAILED], true)) {
                throw ValidationException::withMessages(['resource' => 'Only a failed resource operation can be retried.']);
            }
            $attachment->status = $attachment->isDeleting() ? EnvironmentAttachmentStatus::DELETING : EnvironmentAttachmentStatus::PENDING;
            $attachment->last_error = null;
            $attachment->save();
            SyncEnvironmentResourceAccessJob::dispatch($attachment->id, $attachment->server_id)->afterCommit();
        });
    }

    public function membershipChanged(NetworkServer $member): void
    {
        if ($member->wasRecentlyCreated || $member->wasChanged(['ip', 'server_ip_address_id', 'status'])) {
            DB::afterCommit(fn () => $this->changedServer($member->server_id));
        }
    }

    public function changedServer(int $serverId): void
    {
        $environments = Environment::query()->whereHas('attachments', fn ($query) => $query->where('server_id', $serverId))
            ->orWhereHas('components', fn ($query) => $query->where('server_id', $serverId))->get();
        foreach ($environments as $environment) {
            if ($environment->attachments()->exists()) {
                app(MarkEnvironmentForRedeploy::class)->mark($environment);
                $this->dispatch($environment);
            }
        }
    }

    public function sync(EnvironmentAttachment $attachment): bool
    {
        $attachment->refresh()->load('server', 'attachable', 'environment.application');
        if ($attachment->isDeleting()) {
            $this->remove($attachment);

            return true;
        }
        $service = $attachment->service();
        $handler = $service?->handler();
        if (! $handler instanceof SupportsNetworking || $attachment->server->project_id !== $attachment->environment->application->project_id) {
            throw new RuntimeException('The attached resource is unavailable.');
        }
        $components = $attachment->environment->components()->where('type', ComponentType::APP)
            ->whereNotIn('status', [ComponentStatus::DELETING, ComponentStatus::DELETION_FAILED])->with('server')->get();
        $connections = [];
        foreach ($components as $component) {
            $connection = app(ResolveServerAddress::class)->connection($component->server, $attachment->server);
            if ($connection !== null) {
                $connections[$component->id] = $connection;
            }
        }
        $this->guard($attachment, $handler->networkingPort());
        $this->guardXProtocol($attachment, $handler);
        foreach ($attachment->access ?? [] as $id => $entry) {
            if (! isset($connections[$id]) || $connections[$id]['client_ip'] !== $entry['host']) {
                $this->removeAccess($attachment, $id, $handler instanceof Postgresql && isset($connections[$id]));
            }
        }
        foreach ($connections as $id => $connection) {
            $this->firewall($attachment, $components->find($id), $connection, $handler->networkingPort());
        }
        $this->applyFirewall($attachment->server);
        if ($service->status === ServiceStatus::RESTARTING) {
            return false;
        }
        if ($service->status !== ServiceStatus::READY) {
            throw new RuntimeException('The attached service is not ready.');
        }
        $remote = $components->contains(fn (Component $component): bool => $component->server_id !== $attachment->server_id);
        $ipv6 = collect($connections)->contains(fn (array $connection): bool => str_contains($connection['ip'], ':'));
        if (($remote || $attachment->kind() === 'redis') && (! $handler->networkingEnabled()
            || ($ipv6 && ! ($service->type_data['networking_dual_stack'] ?? false)))) {
            app(ToggleNetworking::class)->enable($service);

            return false;
        }
        if ($attachment->attachable instanceof Database) {
            if ($attachment->attachable->status === DatabaseStatus::CREATING) {
                app(CreateDatabase::class)->provision($attachment->attachable);
            }
            if ($handler instanceof Postgresql) {
                app(CreateDatabaseUser::class)->create($attachment->server, [
                    'username' => $attachment->credentials['username'],
                    'password' => $attachment->credentials['password'], 'host' => 'localhost',
                ], [$attachment->attachable->name], $attachment);
            }
        }
        if ($attachment->kind() === 'redis') {
            if (! $service->secret) {
                throw new RuntimeException('The attached service password is unavailable.');
            }
            $attachment->credentials = ['password' => $service->secret];
            $attachment->save();
        }
        foreach ($components as $component) {
            if (! isset($connections[$component->id])) {
                continue;
            }
            $entry = $attachment->access[$component->id];
            if ($attachment->attachable instanceof Database) {
                $user = app(CreateDatabaseUser::class)->create($attachment->server, [
                    'username' => AttachmentVariables::username($attachment, $component),
                    'password' => $attachment->credentials['password'], 'host' => $entry['host'],
                ], [$attachment->attachable->name], $attachment);
                $entry['database_user_id'] = $user->id;
            }
            $entry['ready'] = true;
            $attachment->jsonUpdate('access', (string) $component->id, $entry);
        }
        if (count($connections) !== $components->count()) {
            throw new RuntimeException('An attached resource private network is unavailable.');
        }
        DB::transaction(function () use ($attachment): void {
            Application::query()->lockForUpdate()->findOrFail($attachment->environment->application_id);
            Environment::query()->lockForUpdate()->findOrFail($attachment->environment_id);
            $attachment->refresh();
            if (! $attachment->isDeleting()) {
                $attachment->status = EnvironmentAttachmentStatus::READY;
                $attachment->last_error = null;
                $attachment->save();
            }
            $this->broadcast($attachment);
        });

        return true;
    }

    public function protectNetworking(Service $service): void
    {
        $handler = $service->handler();
        if (! $handler instanceof SupportsNetworking) {
            return;
        }
        $attachments = EnvironmentAttachment::forService($service)->with('server')->get();
        foreach ($attachments as $attachment) {
            $this->guard($attachment, $handler->networkingPort());
            $this->guardXProtocol($attachment, $handler);
        }
        if ($attachments->isNotEmpty()) {
            $this->applyFirewall($service->server);
        }
    }

    private function guardXProtocol(EnvironmentAttachment $attachment, SupportsNetworking $handler): void
    {
        if (! $handler instanceof Mysql) {
            return;
        }
        $port = $handler->resourceXPort();
        if (! $attachment->firewallRules()->where('type', 'deny')->where('port', $port)->exists()) {
            app(ManageRule::class)->create($attachment->server, [
                'name' => 'Resource X guard', 'type' => 'deny', 'protocol' => 'tcp', 'port' => $port,
            ], attachment: $attachment);
        }
    }

    private function guard(EnvironmentAttachment $attachment, int $port): void
    {
        if ($attachment->firewall_rule_id !== null) {
            return;
        }
        DB::transaction(function () use ($attachment, $port): void {
            $rule = app(ManageRule::class)->create($attachment->server, [
                'name' => 'Resource guard', 'type' => 'deny', 'protocol' => 'tcp', 'port' => $port,
            ], componentManaged: true, attachment: $attachment);
            $attachment->firewall_rule_id = $rule->id;
            $attachment->save();
        });
    }

    /** @param array{network_id: ?int, ip: string, client_ip: string} $connection */
    private function firewall(EnvironmentAttachment $attachment, Component $component, array $connection, int $port): void
    {
        $componentId = $component->id;
        DB::transaction(function () use ($attachment, $component, $componentId, $connection, $port): void {
            $entry = $attachment->access[$componentId] ?? [
                'database_user_id' => null, 'firewall_rule_id' => null, 'ready' => false,
            ];
            $entry['server_id'] = $component->server_id;
            $entry['host'] = $connection['client_ip'];
            $entry['target_host'] = $connection['ip'];
            $entry['network_id'] = $connection['network_id'];
            if ($entry['firewall_rule_id'] === null) {
                $rule = app(ManageRule::class)->create($attachment->server, [
                    'name' => 'Resource access', 'type' => 'allow', 'protocol' => 'tcp', 'port' => $port,
                    'source_any' => false, 'source' => $entry['host'], 'mask' => Cidr::hostPrefix($entry['host']),
                ], componentManaged: true, attachment: $attachment);
                $entry['firewall_rule_id'] = $rule->id;
            }
            $attachment->jsonUpdate('access', (string) $componentId, $entry);
        });
    }

    private function removeAccess(EnvironmentAttachment $attachment, int $componentId, bool $preserveUser = false): void
    {
        $entry = $attachment->access[$componentId];
        $rule = $attachment->firewallRules()->find($entry['firewall_rule_id']);
        if ($rule) {
            app(ManageRule::class)->delete($rule);
            $this->applyFirewall($attachment->server);
        }
        $username = ($attachment->credentials['username'] ?? '').'_'.base_convert((string) $componentId, 10, 36);
        if (! $preserveUser) {
            foreach ($attachment->databaseUsers()->where('username', $username)->get() as $user) {
                app(DeleteDatabaseUser::class)->delete($attachment->server, $user, attachment: $attachment);
            }
        }
        DB::transaction(function () use ($attachment, $componentId, $rule, $entry, $preserveUser): void {
            if ($rule) {
                $rule->delete();
                $this->broadcastRule($rule, deleted: true);
            }
            if ($preserveUser) {
                $entry['firewall_rule_id'] = null;
                $entry['ready'] = false;
                $attachment->jsonUpdate('access', (string) $componentId, $entry);
            } else {
                $attachment->jsonForget('access', (string) $componentId);
            }
        });
    }

    private function remove(EnvironmentAttachment $attachment): void
    {
        $attachment->firewallRules()->update(['status' => FirewallRuleStatus::DELETING]);
        if ($attachment->firewallRules()->exists()) {
            $this->applyFirewall($attachment->server);
        }
        foreach ($attachment->databaseUsers()->get()->sortBy(fn ($user): bool => $user->username === ($attachment->credentials['username'] ?? null)) as $user) {
            app(DeleteDatabaseUser::class)->delete($attachment->server, $user, attachment: $attachment);
        }
        DB::transaction(function () use ($attachment): void {
            $rules = $attachment->firewallRules()->get();
            $attachment->firewallRules()->delete();
            if ($rules->isNotEmpty()) {
                $this->applyFirewall($attachment->server);
            }
            foreach ($rules as $rule) {
                $this->broadcastRule($rule, deleted: true);
            }
            if ($attachment->attachable instanceof Database && $attachment->attachable->status === DatabaseStatus::CREATING) {
                $attachment->attachable->update(['status' => DatabaseStatus::FAILED]);
            }
            $attachment->delete();
            $this->broadcast($attachment);
        });
    }

    private function applyFirewall(Server $server): void
    {
        $service = $server->firewall();
        if ($service?->status !== ServiceStatus::READY) {
            throw new RuntimeException('The resource firewall is not ready.');
        }
        /** @var Firewall $handler */
        $handler = $service->handler();
        $handler->applyRules();
        foreach ($server->firewallRules()->whereNotNull('environment_attachment_id')->where('status', '!=', FirewallRuleStatus::DELETING)->get() as $rule) {
            $rule->status = FirewallRuleStatus::READY;
            $rule->save();
            $this->broadcastRule($rule);
        }
    }

    public function failed(int $attachmentId): void
    {
        $attachment = EnvironmentAttachment::query()->find($attachmentId);
        if ($attachment) {
            $attachment->status = $attachment->isDeleting() ? EnvironmentAttachmentStatus::DELETION_FAILED : EnvironmentAttachmentStatus::FAILED;
            $attachment->last_error = 'Resource access synchronization failed. Check the resource service, firewall and private network, then retry. No database or data was deleted.';
            $attachment->save();
            $this->broadcast($attachment);
        }
    }

    private function broadcastRule(FirewallRule $rule, bool $deleted = false): void
    {
        $event = new SocketEventDTO(projectId: $rule->server->project_id,
            type: $deleted ? 'firewall-rule.deleted' : 'firewall-rule.updated',
            data: $deleted ? ['id' => $rule->id] : new FirewallRuleResource($rule));
        DB::afterCommit(static fn () => SocketEvent::dispatch($event));
    }

    private function broadcast(EnvironmentAttachment $attachment): void
    {
        $event = new SocketEventDTO(projectId: $attachment->environment->application->project_id, type: 'environment.updated',
            data: ['id' => $attachment->environment_id, 'application_id' => $attachment->environment->application_id]);
        DB::afterCommit(static fn () => SocketEvent::dispatch($event));
    }
}
