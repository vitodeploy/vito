<?php

namespace App\Actions\Server\Security;

use App\Enums\SecurityControlStatus;
use App\Jobs\Server\Security\ApplyPasswordAuthJob;
use App\Models\Server;
use Illuminate\Support\Facades\Validator;

class ManagePasswordAuth
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function update(Server $server, array $input): void
    {
        Validator::make($input, [
            'enabled' => ['required', 'boolean'],
        ])->validate();

        $enabled = (bool) $input['enabled'];

        $server->refresh();
        $security = $server->feature_data['security'] ?? [];
        $security['password_authentication'] = array_merge($security['password_authentication'] ?? [], [
            'enabled' => $enabled,
            'status' => SecurityControlStatus::UPDATING->value,
        ]);
        $server->jsonUpdate('feature_data', 'security', $security);

        dispatch(new ApplyPasswordAuthJob($server, $enabled))->onQueue('ssh');
    }
}
