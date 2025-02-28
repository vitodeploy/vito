<?php

namespace App\Actions\Site;

use App\Models\Command;
use App\Models\Site;
use App\Models\User;

class CreateCommand
{
    public function create(User $user, Site $site, array $input): Command
    {
        $script = new Command([
            'user_id' => $user->id,
            'name' => $input['name'],
            'command' => $input['command'],
            'site_id' => $site->id,
        ]);
        $script->save();

        return $script;
    }

    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'command' => ['required', 'string'],
        ];
    }
}
