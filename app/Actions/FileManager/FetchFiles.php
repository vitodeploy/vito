<?php

namespace App\Actions\FileManager;

use App\Exceptions\SSHError;
use App\Models\File;
use App\Models\Server;
use App\Models\User;
use Illuminate\Validation\Rule;

class FetchFiles
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws SSHError
     */
    public function fetch(User $user, Server $server, array $input): void
    {
        File::parse(
            $user,
            $server,
            $input['path'],
            $input['user'],
            $server->os()->ls($input['path'], $input['user'])
        );
    }

    /**
     * @return array<string, array<string>>
     */
    public static function rules(Server $server): array
    {
        return [
            'path' => [
                'required',
            ],
            'user' => [
                'required',
                Rule::in($server->getSshUsers()),
            ],
        ];
    }
}
