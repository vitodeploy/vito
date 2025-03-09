<?php

namespace App\Actions\Server;

use App\Models\Server;
use Illuminate\Validation\ValidationException;

class CreateServerLog
{
    /**
     * @param  array<string, mixed>  $input
     *
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

    /**
     * @return array<string, string>
     */
    public static function rules(): array
    {
        return [
            'path' => 'required',
        ];
    }
}
