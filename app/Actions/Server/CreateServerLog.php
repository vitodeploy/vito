<?php

namespace App\Actions\Server;

use App\Models\Server;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CreateServerLog
{
    /**
     * @throws ValidationException
     */
    public function create(Server $server, array $input): void
    {
        $this->validate($input);

        $server->logs()->create([
            'is_remote' => true,
            'name' => $input['path'],
            'type' => 'remote',
            'disk' => 'ssh',
        ]);
    }

    /**
     * @throws ValidationException
     */
    protected function validate(array $input): void
    {
        Validator::make($input, [
            'path' => 'required',
        ])->validate();
    }
}
