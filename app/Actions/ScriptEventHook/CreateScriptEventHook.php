<?php

namespace App\Actions\ScriptEventHook;

use App\Enums\ScriptEventHookEvent;
use App\Models\Script;
use App\Models\ScriptEventHook;
use App\Models\Server;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateScriptEventHook
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function create(User $user, Script $script, array $input): ScriptEventHook
    {
        $server = $this->validate($input);

        if (! $user->can('update', $server)) {
            abort(403, 'You do not have permission to run scripts on this server.');
        }

        $hook = new ScriptEventHook([
            'script_id' => $script->id,
            'user_id' => $user->id,
            'project_id' => $server->project_id,
            'server_id' => $server->id,
            'event' => $input['event'],
            'user' => $input['user'],
            'enabled' => $input['enabled'] ?? true,
        ]);
        $hook->save();

        return $hook;
    }

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    private function validate(array $input): Server
    {
        $server = null;
        $users = ['root'];
        if (isset($input['server_id'])) {
            $server = Server::query()->find($input['server_id']);
            if ($server) {
                $users = $server->getSshUsers();
            }
        }

        Validator::make($input, [
            'event' => [
                'required',
                Rule::enum(ScriptEventHookEvent::class),
            ],
            'server_id' => [
                'required',
                Rule::exists('servers', 'id'),
            ],
            'user' => [
                'required',
                Rule::in($users),
            ],
            'enabled' => [
                'boolean',
            ],
        ])->validate();

        /** @var Server $server */
        return $server;
    }
}
