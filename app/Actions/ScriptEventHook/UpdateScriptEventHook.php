<?php

namespace App\Actions\ScriptEventHook;

use App\Enums\ScriptEventHookEvent;
use App\Models\ScriptEventHook;
use App\Models\Server;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpdateScriptEventHook
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function update(ScriptEventHook $hook, User $user, array $input): ScriptEventHook
    {
        $server = $this->validate($input);

        if ($server && ! $user->can('update', $server)) {
            abort(403, 'You do not have permission to run scripts on this server.');
        }

        $hook->fill([
            'event' => $input['event'] ?? $hook->event->value,
            'server_id' => $server->id ?? $hook->server_id,
            'project_id' => $server->project_id ?? $hook->project_id,
            'user' => $input['user'] ?? $hook->user,
            'enabled' => $input['enabled'] ?? $hook->enabled,
        ]);
        $hook->save();

        return $hook;
    }

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    private function validate(array $input): ?Server
    {
        $users = ['root'];
        $server = null;
        if (isset($input['server_id'])) {
            $server = Server::query()->find($input['server_id']);
            if ($server) {
                $users = $server->getSshUsers();
            }
        }

        Validator::make($input, [
            'event' => [
                'sometimes',
                Rule::enum(ScriptEventHookEvent::class),
            ],
            'server_id' => [
                'sometimes',
                Rule::exists('servers', 'id'),
            ],
            'user' => [
                'required_with:server_id',
                Rule::in($users),
            ],
            'enabled' => [
                'sometimes',
                'boolean',
            ],
        ])->validate();

        return $server;
    }
}
