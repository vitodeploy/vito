<?php

namespace App\Actions\Site;

use App\Models\Command;
use App\Models\Site;

class CreateCommand
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function create(Site $site, array $input): Command
    {
        $script = new Command([
            'site_id' => $site->id,
            'name' => $input['name'],
            'command' => $input['command'],
        ]);
        $script->save();

        return $script;
    }

    /**
     * @return array<string, array<string>>
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'command' => ['required', 'string'],
        ];
    }
}
