<?php

namespace App\Actions\Server;

use App\Actions\Network\ResyncServerEndpoint;
use App\Models\Server;
use App\ValidationRules\RestrictedIPAddressesRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EditServer
{
    public function __construct(private ResyncServerEndpoint $resync) {}

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
            $this->resync->handle($server);
        }

        if ($checkConnection) {
            return $server->checkConnection();
        }

        return $server;
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
                'ip',
                new RestrictedIPAddressesRule,
                Rule::unique('servers')->where('project_id', $server->project_id)->ignore($server->id),
            ],
            'local_ip' => [
                'nullable',
                'string',
                'ip',
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
