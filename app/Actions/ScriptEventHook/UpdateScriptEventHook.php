<?php

namespace App\Actions\ScriptEventHook;

use App\Enums\ScriptEventHookEvent;
use App\Models\ScriptEventHook;
use App\Models\Server;
use App\Models\User;
use App\Traits\HasRolePolicies;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpdateScriptEventHook
{
    use HasRolePolicies;

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function update(ScriptEventHook $hook, User $user, array $input): ScriptEventHook
    {
        $this->validate($user, $input);

        $hook->fill([
            'event' => $input['event'] ?? $hook->event->value,
            'server_id' => $input['server_id'] ?? $hook->server_id,
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
    private function validate(User $user, array $input): void
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

        if ($server && ! $this->hasWriteAccess($user, $server->project)) {
            throw ValidationException::withMessages(['server_id' => "You do not have write access to this server's project."]);
        }
    }
}
