<?php

namespace App\Actions\Server;

use App\Actions\Network\DispatchNetworkServerSync;
use App\Enums\NetworkServerStatus;
use App\Enums\NetworkType;
use App\Models\NetworkServer;
use App\Models\Server;
use App\ValidationRules\RestrictedIPAddressesRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EditServer
{
    public function __construct(private DispatchNetworkServerSync $sync) {}

    /**
     * @param  array<string, mixed>  $input
     * @return Server $server
     *
     * @throws ValidationException
     */
    public function edit(Server $server, array $input): Server
    {
        $this->validate($server, $input);

        $checkConnection = false;
        $ipChanged = false;
        if (isset($input['name'])) {
            $server->name = $input['name'];
        }
        if (isset($input['ip'])) {
            if ($server->ip !== $input['ip']) {
                $checkConnection = true;
                $ipChanged = true;
            }
            $server->ip = $input['ip'];
        }
        if (isset($input['local_ip'])) {
            $server->local_ip = $input['local_ip'];
        }
        if (isset($input['port'])) {
            if ($server->port !== $input['port']) {
                $checkConnection = true;
            }
            $server->port = $input['port'];
        }
        $server->save();

        if ($ipChanged) {
            $this->resyncWireGuardPeers($server);
        }

        if ($checkConnection) {
            return $server->checkConnection();
        }

        return $server;
    }

    /**
     * The server's public IP feeds every peer's WireGuard endpoint and handshake
     * firewall rule, so re-sync the peers in each of its WireGuard networks.
     */
    private function resyncWireGuardPeers(Server $server): void
    {
        NetworkServer::query()
            ->where('server_id', $server->id)
            ->where('status', '!=', NetworkServerStatus::LEAVING)
            ->whereHas('network', fn ($query) => $query->where('type', NetworkType::WIREGUARD))
            ->with('network')
            ->get()
            ->each(fn (NetworkServer $membership) => $this->sync->resyncMembers($membership->network, $membership->id));
    }

    private function validate(Server $server, array $input): void
    {
        $rules = [
            'name' => [
                'required',
                'max:255',
                Rule::unique('servers')->where('project_id', $server->project_id)->ignore($server->id),
            ],
            'ip' => [
                'string',
                new RestrictedIPAddressesRule,
                Rule::unique('servers')->where('project_id', $server->project_id)->ignore($server->id),
            ],
            'local_ip' => [
                'nullable',
                'string',
                Rule::unique('servers')->where('project_id', $server->project_id)->ignore($server->id),
            ],
            'port' => [
                'integer',
                'min:1',
                'max:65535',
            ],
        ];

        Validator::make($input, $rules)->validate();
    }
}
