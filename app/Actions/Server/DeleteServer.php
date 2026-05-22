<?php

namespace App\Actions\Server;

use App\Models\Server;
use App\ServerProviders\Custom;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DeleteServer
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function delete(Server $server, array $input): void
    {
        $this->validate($server, $input);

        if (array_key_exists('delete_from_provider', $input)) {
            $server->deleteFromProvider = filter_var($input['delete_from_provider'], FILTER_VALIDATE_BOOLEAN);
        }

        $server->delete();
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validate(Server $server, array $input): void
    {
        $rules = [
            'name' => [
                'required',
                Rule::in([$server->name]),
            ],
            'delete_from_provider' => [
                Rule::requiredIf(fn (): bool => $server->provider !== Custom::id()),
                'boolean',
            ],
        ];

        Validator::make($input, $rules)->validate();
    }
}
