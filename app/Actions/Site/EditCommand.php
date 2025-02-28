<?php

namespace App\Actions\Site;

use App\Models\Command;
use App\Models\Site;
use App\Models\User;

class EditCommand
{
    public function edit(Command $command, User $user, Site $site, array $input): Command
    {
        $command->name = $input['name'];
        $command->command = $input['command'];
        $command->site_id = $site->id;
        $command->save();

        return $command;
    }

    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'command' => ['required', 'string'],
        ];
    }
}
