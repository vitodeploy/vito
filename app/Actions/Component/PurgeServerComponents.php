<?php

namespace App\Actions\Component;

use App\Jobs\Component\DeletePurgedComponentDeployKeyJob;
use App\Models\Environment;
use App\Models\Server;
use Illuminate\Support\Facades\DB;
use Throwable;

class PurgeServerComponents
{
    /**
     * @return array{environments: list<int>, keys: list<array{id: int, server_id: int, environment_id: int, deploy_key_source_control_id: ?int, deploy_key_repository: ?string, deploy_key_id: ?string, ssh_key: ?string}>}
     */
    public function purge(Server $server): array
    {
        $server = Server::query()->lockForUpdate()->findOrFail($server->id);
        $components = $server->components()->lockForUpdate()
            ->get(['id', 'server_id', 'environment_id', 'deploy_key_source_control_id', 'deploy_key_repository', 'deploy_key_id', 'ssh_key']);
        $departure = [
            'environments' => $components->pluck('environment_id')->unique()->values()->all(),
            'keys' => $components->filter(fn ($component) => $component->deploy_key_id !== null || $component->deploy_key_source_control_id !== null)->values()->toArray(),
        ];
        $server->components()->delete();

        return $departure;
    }

    /**
     * @param  array{environments: list<int>, keys: list<array{id: int, server_id: int, environment_id: int, deploy_key_source_control_id: ?int, deploy_key_repository: ?string, deploy_key_id: ?string, ssh_key: ?string}>}  $departure
     */
    public function handle(array $departure): void
    {
        DB::afterCommit(function () use ($departure): void {
            foreach ($departure['keys'] as $key) {
                try {
                    DeletePurgedComponentDeployKeyJob::dispatch($key['id'], $key['server_id'], $key['environment_id'], $key['deploy_key_source_control_id'], $key['deploy_key_repository'], $key['deploy_key_id'], $key['ssh_key']);
                } catch (Throwable) {
                    $this->failed($key['environment_id']);
                }
            }
        });
    }

    public function failed(int $environmentId): void
    {
        Environment::query()->whereKey($environmentId)->update([
            'last_error' => 'A removed server component deploy key could not be removed. Remove the key from the source control manually.',
        ]);
    }
}
