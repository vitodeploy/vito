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
        Validator::make($input, self::rules($user))->validate();

        $server->project_id = $input['project_id'];
        $server->save();

        return $server;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(User $user): array
    {
        return [
            'project_id' => [
                'required',
                Rule::in($user->allProjects()->pluck('id')->toArray()),
            ],
        ];
    }
}
