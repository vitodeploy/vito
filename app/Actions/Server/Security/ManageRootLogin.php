<?php

namespace App\Actions\Server\Security;

use App\Enums\SecurityControlStatus;
use App\Jobs\Server\Security\ApplyRootLoginJob;
use App\Models\Server;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ManageRootLogin
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function update(Server $server, array $input): void
    {
        Validator::make($input, [
            'enabled' => ['required', 'boolean'],
        ])->validate();

        $enabled = (bool) $input['enabled'];

        if (! $enabled && $server->getSshUser() === 'root') {
            throw ValidationException::withMessages([
                'enabled' => 'Vito connects to this server as root. Switch to a non-root SSH user before disabling root login.',
            ]);
        }

        $server->refresh();
        $security = $server->feature_data['security'] ?? [];
        $security['root_login'] = array_merge($security['root_login'] ?? [], [
            'enabled' => $enabled,
            'status' => SecurityControlStatus::UPDATING->value,
        ]);
        $server->jsonUpdate('feature_data', 'security', $security);

        dispatch(new ApplyRootLoginJob($server, $enabled))->onQueue('ssh');
    }
}
