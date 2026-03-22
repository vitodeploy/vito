<?php

namespace App\Actions\Site;

use App\Models\Site;

class UpdateVhost
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function update(Site $site, array $input): void
    {
        $validated = validator($input, [
            'vhost' => ['required', 'string', 'max:65535'],
        ])->validate();

        $site->webserver()->updateVHost($site, $validated['vhost']);
    }
}
