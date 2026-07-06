<?php

namespace App\Actions\ScriptEventHook;

use App\Enums\ScriptEventHookEvent;
use App\Models\Project;
use App\Models\Script;
use App\Models\ScriptEventHook;
use App\Models\Server;
use App\Models\User;
use App\Traits\HasRolePolicies;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateScriptEventHook
{
    use HasRolePolicies;

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function create(User $user, Script $script, array $input): ScriptEventHook
    {
        $this->validate($user, $input);

        /** @var Server $server */
        $server = Server::query()->findOrFail($input['server_id']);

        $hook = new ScriptEventHook([
            'script_id' => $script->id,
            'user_id' => $user->id,
            'project_id' => $input['project_id'],
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
    private function validate(User $user, array $input): void
    {
        $users = ['root'];
        if (isset($input['server_id'])) {
            $server = Server::query()->find($input['server_id']);
            if ($server) {
                $users = $server->getSshUsers();
            }
        }

        Validator::make($input, [
            'project_id' => [
                'required',
                Rule::exists('projects', 'id'),
            ],
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

        /** @var Project $project */
        $project = Project::query()->findOrFail($input['project_id']);
        if (! $this->hasWriteAccess($user, $project)) {
            throw ValidationException::withMessages(['project_id' => 'You do not have write access to this project.']);
        }

        /** @var Server $server */
        $server = Server::query()->findOrFail($input['server_id']);
        if (! $this->hasWriteAccess($user, $server->project)) {
            throw ValidationException::withMessages(['server_id' => 'You do not have write access to this server\'s project.']);
        }
    }
}
