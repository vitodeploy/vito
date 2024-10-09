<?php

namespace App\Actions\Server;

use App\Models\Server;
use Illuminate\Validation\ValidationException;

class CreateServerLog
{
    /**
     * @throws ValidationException
     */
    public function create(Server $server, array $input): void
    {
        $server->logs()->create([
            'is_remote' => true,
            'name' => $input['path'],
            'type' => 'remote',
            'disk' => 'ssh',
        ]);
    }

    public static function rules(): array
    {
        return [
            'path' => 'required',
        ];
    }
}
