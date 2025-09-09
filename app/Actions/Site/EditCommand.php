<?php

namespace App\Actions\Site;

use App\Models\Command;
use Illuminate\Support\Facades\Validator;

class EditCommand
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function edit(Command $command, array $input): Command
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'command' => ['required', 'string'],
        ])->validate();

        $command->name = $input['name'];
        $command->command = $input['command'];
        $command->save();

        return $command;
    }
}
