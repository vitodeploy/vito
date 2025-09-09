<?php

namespace App\Actions\Site;

use App\Models\Command;
use App\Models\Site;
use Illuminate\Support\Facades\Validator;

class CreateCommand
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function create(Site $site, array $input): Command
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'command' => ['required', 'string'],
        ])->validate();

        $script = new Command([
            'site_id' => $site->id,
            'name' => $input['name'],
            'command' => $input['command'],
        ]);
        $script->save();

        return $script;
    }
}
