<?php

namespace App\Actions\Server;

use App\Models\Server;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TransferServer
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function transfer(User $user, Server $server, array $input): Server
    {
        $this->validate($user, $input);

        $server->project_id = $input['project_id'];
        $server->save();

        return $server;
    }

    private function validate(User $user, array $input): void
    {
        $rules = [
            'project_id' => [
                'required',
                Rule::in($user->allProjects()->pluck('id')->toArray()),
            ],
        ];

        Validator::make($input, $rules)->validate();
    }
}
