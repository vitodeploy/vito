<?php

namespace App\Actions\Site;

use App\Exceptions\SSHError;
use App\Models\ComponentBackend;
use App\Models\ComponentCapability;
use App\Models\Server;
use App\Models\Site;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdatePort
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws SSHError
     */
    public function update(Site $site, array $input): void
    {
        $validated = Validator::make($input, [
            'port' => ['required', 'integer', 'between:1024,65535'],
        ])->validate();

        DB::transaction(function () use ($site, $validated): void {
            Server::query()->lockForUpdate()->findOrFail($site->server_id);
            if (ComponentBackend::query()->where('port', $validated['port'])
                ->whereHas('targetComponent', fn ($query) => $query->where('server_id', $site->server_id))->exists()) {
                throw ValidationException::withMessages(['port' => 'This port is used by a load balancer backend.']);
            }
            if (ComponentCapability::query()->where('capability', 'web')
                ->whereHas('component', fn ($query) => $query->where('server_id', $site->server_id))
                ->pluck('config')->contains(fn (?array $config): bool => (int) ($config['port'] ?? 0) === (int) $validated['port'])) {
                throw ValidationException::withMessages(['port' => 'This port is used by another component on this server.']);
            }
            $site->port = (int) $validated['port'];
            $site->save();
        });

        $site->webserver()->updateVHost($site);

        app(BroadcastSiteUpdate::class)->broadcast($site);
    }
}
