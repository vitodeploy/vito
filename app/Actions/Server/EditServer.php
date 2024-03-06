<?php

namespace App\Actions\Server;

use App\Models\Server;
use App\ValidationRules\RestrictedIPAddressesRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class EditServer
{
    /**
     * @throws ValidationException
     */
    public function edit(Server $server, array $input): Server
    {
        $this->validate($input);

        $checkConnection = false;
        if (isset($input['name'])) {
            $server->name = $input['name'];
        }
        if (isset($input['ip'])) {
            if ($server->ip !== $input['ip']) {
                $checkConnection = true;
            }
            $server->ip = $input['ip'];
        }
        if (isset($input['port'])) {
            if ($server->port !== $input['port']) {
                $checkConnection = true;
            }
            $server->port = $input['port'];
        }
        $server->save();

        if ($checkConnection) {
            return $server->checkConnection();
        }

        return $server;
    }

    /**
     * @throws ValidationException
     */
    protected function validate(array $input): void
    {
        Validator::make($input, [
            'ip' => [
                new RestrictedIPAddressesRule(),
            ],
        ])->validateWithBag('editServer');
    }
}
